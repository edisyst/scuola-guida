<?php

namespace App\Services;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Notifications\InstructorStudentOutcome;
use App\Services\BadgeService;
use App\Services\NotificationService;
use App\Services\ReviewErrorsService;
use App\Services\SpacedRepetitionService;
use App\Services\StreakService;

class QuizAttemptService
{
    public function __construct(
        private QuizEnrollmentService $enrollmentService,
        private SpacedRepetitionService $spacedRepetitionService,
        private StreakService $streakService,
        private BadgeService $badgeService,
        private QuestionVersionService $versionService,
        private NotificationService $notificationService,
    ) {}

    /**
     * Crea un nuovo tentativo calcolando lo score.
     * Se il quiz è confermato, consuma l'iscrizione approvata del viewer.
     */
    public function record(int $userId, int $quizId, array $answers, ?int $duration): QuizAttempt
    {
        $quiz = Quiz::with('questions:id,is_true')->findOrFail($quizId);

        $correctMap = $quiz->questions->pluck('is_true', 'id');

        $enrollmentId = null;

        if ($quiz->isConfirmed()) {
            $user       = \App\Models\User::findOrFail($userId);
            $enrollment = $this->enrollmentService->activeFor($quiz, $user);

            if (!$enrollment || !$enrollment->isApproved()) {
                abort(403, 'Iscrizione approvata richiesta per svolgere questo quiz.');
            }

            $enrollmentId = $enrollment->id;
        }

        $normalized = $this->normalizeAnswers($answers);
        $normalized = $this->injectVersionIds($normalized);

        $attempt = QuizAttempt::create([
            'user_id'            => $userId,
            'quiz_id'            => $quiz->id,
            'quiz_enrollment_id' => $enrollmentId,
            'score'              => $this->scoreAnswers($answers, $correctMap),
            'total_questions'    => $correctMap->count(),
            'duration'           => $duration,
            'answers'            => $normalized,
        ]);

        if ($enrollmentId) {
            // $enrollment è già caricato sopra: evita la lazy query su $attempt->enrollment (W-5).
            $this->enrollmentService->markCompleted($enrollment, $attempt);
        }

        // Aggiorna spaced repetition per ogni risposta del tentativo.
        $user = User::find($userId);
        if ($user) {
            foreach ($answers as $questionId => $answer) {
                $result    = is_array($answer) ? (int) ($answer['correct'] ?? 0) : (int) $answer;
                $isCorrect = isset($correctMap[$questionId])
                    && $result === (int) $correctMap[$questionId];
                $this->spacedRepetitionService->recordAnswer($user, (int) $questionId, $isCorrect);
            }

            $this->streakService->recordActivity($user);
            $this->badgeService->checkAllBadges($user);
            ReviewErrorsService::forgetErrorCountCache($user->id);

            $instructors = $user->instructors()->get();
            foreach ($instructors as $instructor) {
                $this->notificationService->send(
                    $instructor,
                    new InstructorStudentOutcome($user, $quiz, $attempt)
                );
            }
        }

        return $attempt;
    }

    /**
     * Aggiorna un tentativo esistente ricalcolando lo score.
     */
    public function updateAttempt(QuizAttempt $attempt, array $answers, ?int $duration): QuizAttempt
    {
        $quiz = $attempt->quiz()->with('questions:id,is_true')->first();
        $correctMap = $quiz->questions->pluck('is_true', 'id');

        $normalized = $this->normalizeAnswers($answers);

        // Preserva i question_version_id già registrati; aggiunge quelli mancanti.
        $normalized = $this->injectVersionIds($normalized, $attempt->answers ?? []);

        $attempt->update([
            'answers'         => $normalized,
            'score'           => $this->scoreAnswers($answers, $correctMap),
            'total_questions' => $correctMap->count(),
            'duration'        => $duration ?? $attempt->duration,
        ]);

        return $attempt;
    }

    /**
     * Prepara tutti i dati necessari per la view di dettaglio di un tentativo.
     * Per ogni domanda usa la QuestionVersion referenziata nel tentativo (se
     * disponibile) per mostrare testo e risposta corretta come il viewer li ha
     * effettivamente visti, anche dopo modifiche successive alla domanda.
     */
    public function getAttemptDetail(QuizAttempt $attempt): array
    {
        $attempt->loadMissing(['quiz', 'user']);
        $quiz = $attempt->quiz;

        $quizQuestions = $quiz->questions()->with('category')->get();

        // Carica le versioni storiche referenziate dal tentativo in una singola query.
        $versionMap = $this->versionService->buildVersionMapForAttempt(
            $attempt,
            $quizQuestions->pluck('id')->all()
        );

        $questionsCollection = $quizQuestions->map(function ($question, $pivotIndex) use ($attempt, $versionMap) {
            $qid     = $question->id;
            $version = $versionMap[$qid] ?? null;

            $userAnswer    = $attempt->getAnswerResult($qid);
            $position      = $attempt->getAnswerPosition($qid);
            $timeSpent     = $attempt->getTimeSpent($qid);

            // Risposta corretta e testo provengono dalla versione storica se disponibile.
            $correctAnswer = $version !== null ? (int) $version->is_true : (int) $question->is_true;
            $isCorrect     = $userAnswer !== null ? ($userAnswer === $correctAnswer) : null;

            $isHistorical = $version !== null
                && $this->versionService->isHistoricalVersion($version, $question);

            return [
                'question'       => $question,
                'version'        => $version,
                'is_historical'  => $isHistorical,
                'user_answer'    => $userAnswer,
                'correct_answer' => $correctAnswer,
                'is_correct'     => $isCorrect,
                'position'       => $position,
                'time_spent'     => $timeSpent,
                '_pivot_index'   => $pivotIndex,
            ];
        });

        // Answered questions sorted by position; unpositioned appended in pivot order.
        $withPos    = $questionsCollection->filter(fn ($i) => $i['position'] !== null)->sortBy('position');
        $withoutPos = $questionsCollection->filter(fn ($i) => $i['position'] === null)->sortBy('_pivot_index');

        $questions = $withPos->concat($withoutPos)
            ->map(function ($item) {
                unset($item['_pivot_index']);
                return $item;
            })
            ->values();

        $total       = $questions->count();
        $correct     = $questions->filter(fn ($i) => $i['is_correct'] === true)->count();
        $wrong       = $questions->filter(fn ($i) => $i['is_correct'] === false)->count();
        $notAnswered = $questions->filter(fn ($i) => $i['user_answer'] === null)->count();
        $answered    = $total - $notAnswered;
        $percentage  = $total > 0 ? round($correct / $total * 100, 1) : 0.0;
        $passed      = $quiz->max_errors !== null
            ? $wrong < $quiz->max_errors
            : $percentage >= 60.0;

        $durationHuman = null;
        if ($attempt->duration) {
            $mins = intdiv($attempt->duration, 60);
            $secs = $attempt->duration % 60;
            $durationHuman = $mins > 0 ? "{$mins} min {$secs} sec" : "{$secs} sec";
        }

        return [
            'attempt'   => $attempt,
            'quiz'      => $quiz,
            'stats'     => [
                'total'          => $total,
                'answered'       => $answered,
                'correct'        => $correct,
                'wrong'          => $wrong,
                'not_answered'   => $notAnswered,
                'percentage'     => $percentage,
                'passed'         => $passed,
                'duration_human' => $durationHuman,
            ],
            'questions' => $questions,
        ];
    }

    /**
     * Calcola il numero di risposte corrette confrontandole con la mappa truth.
     * Gestisce sia il formato esteso { correct: 0|1, ... } sia il formato flat legacy.
     *
     * @param  array  $answers      [question_id => 0|1]  oppure  [question_id => {correct: 0|1, ...}]
     * @param  iterable  $correctMap [question_id => bool]
     */
    private function scoreAnswers(array $answers, $correctMap): int
    {
        $score = 0;

        foreach ($answers as $questionId => $answer) {
            if (!isset($correctMap[$questionId])) {
                continue;
            }

            $result = is_array($answer) ? (int) ($answer['correct'] ?? 0) : (int) $answer;

            if ($result === (int) $correctMap[$questionId]) {
                $score++;
            }
        }

        return $score;
    }

    /**
     * Converte il formato flat legacy nel formato esteso, lasciando invariato
     * ciò che è già nel nuovo formato. Empty string → null per campi nullable.
     *
     * @param  array  $answers  [question_id => 0|1]  oppure  [question_id => {correct: 0|1, ...}]
     * @return array            [question_id => {correct: 0|1, answered_at: int|null, ...}]
     */
    private function normalizeAnswers(array $answers): array
    {
        $normalized = [];

        foreach ($answers as $questionId => $answer) {
            if (is_array($answer)) {
                $toInt = fn ($v) => ($v !== null && $v !== '') ? (int) $v : null;
                $normalized[$questionId] = [
                    'correct'            => (int) ($answer['correct'] ?? 0),
                    'answered_at'        => $toInt($answer['answered_at'] ?? null),
                    'time_spent_seconds' => $toInt($answer['time_spent_seconds'] ?? null),
                    'position'           => $toInt($answer['position'] ?? null),
                ];
            } else {
                $normalized[$questionId] = [
                    'correct'            => (int) $answer,
                    'answered_at'        => null,
                    'time_spent_seconds' => null,
                    'position'           => null,
                ];
            }
        }

        return $normalized;
    }

    /**
     * Inietta il question_version_id nelle risposte normalizzate.
     *
     * Se $existingAnswers (le risposte già salvate sul tentativo) contengono già
     * un question_version_id per una domanda, viene preservato — così si registra
     * la versione che il viewer ha effettivamente visto alla prima risposta, non
     * quella al momento dell'autosave successivo.
     *
     * Per le domande senza version_id precedente si esegue una singola query batch.
     *
     * @param  array  $normalized     [question_id => {correct, answered_at, ...}]
     * @param  array  $existingAnswers [question_id => {correct, ..., question_version_id?}]
     * @return array
     */
    private function injectVersionIds(array $normalized, array $existingAnswers = []): array
    {
        $needLookup = [];

        foreach (array_keys($normalized) as $qid) {
            $existing = $existingAnswers[$qid] ?? null;
            if (is_array($existing) && !empty($existing['question_version_id'])) {
                // Già presente: preserva senza query.
                $normalized[$qid]['question_version_id'] = (int) $existing['question_version_id'];
            } else {
                $needLookup[] = (int) $qid;
                $normalized[$qid]['question_version_id'] = null; // placeholder
            }
        }

        if (empty($needLookup)) {
            return $normalized;
        }

        $versionMap = $this->versionService->latestVersionIdMap($needLookup);

        foreach ($needLookup as $qid) {
            $normalized[$qid]['question_version_id'] = $versionMap[$qid] ?? null;
        }

        return $normalized;
    }
}
