<?php

namespace App\Services;

use App\Models\Quiz;
use App\Models\QuizAttempt;

class QuizAttemptService
{
    public function __construct(private QuizEnrollmentService $enrollmentService) {}

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

        $attempt->update([
            'answers'         => $normalized,
            'score'           => $this->scoreAnswers($answers, $correctMap),
            'total_questions' => $correctMap->count(),
            'duration'        => $duration ?? $attempt->duration,
        ]);

        return $attempt;
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
}
