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

        $attempt = QuizAttempt::create([
            'user_id'            => $userId,
            'quiz_id'            => $quiz->id,
            'quiz_enrollment_id' => $enrollmentId,
            'score'              => $this->scoreAnswers($answers, $correctMap),
            'total_questions'    => $correctMap->count(),
            'duration'           => $duration,
            'answers'            => $answers,
        ]);

        if ($enrollmentId) {
            $this->enrollmentService->markCompleted($attempt->enrollment, $attempt);
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

        $attempt->update([
            'answers'         => $answers,
            'score'           => $this->scoreAnswers($answers, $correctMap),
            'total_questions' => $correctMap->count(),
            'duration'        => $duration ?? $attempt->duration,
        ]);

        return $attempt;
    }

    /**
     * Calcola il numero di risposte corrette confrontandole con la mappa truth.
     *
     * @param  array  $answers      [question_id => "0"|"1"]
     * @param  iterable  $correctMap [question_id => bool]
     */
    private function scoreAnswers(array $answers, $correctMap): int
    {
        $score = 0;

        foreach ($answers as $questionId => $answer) {
            // Salta domande non appartenenti al quiz (input manomesso o race condition).
            if (!isset($correctMap[$questionId])) {
                continue;
            }

            if ((int) $answer === (int) $correctMap[$questionId]) {
                $score++;
            }
        }

        return $score;
    }
}
