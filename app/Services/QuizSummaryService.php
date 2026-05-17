<?php

namespace App\Services;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizEnrollment;
use Illuminate\Support\Collection;

class QuizSummaryService
{
    /**
     * Restituisce KPI + collection iscritti arricchita per il pannello di riepilogo.
     *
     * @return array{
     *     kpi: array{total: int, completed: int, pending: int, average_score: ?float, pass_rate: ?float},
     *     enrollments: Collection<int, QuizEnrollment>
     * }
     */
    public function getSummary(Quiz $quiz): array
    {
        $enrollments = $quiz->enrollments()
            ->with(['user', 'quizAttempt'])
            ->get()
            ->sortBy(fn (QuizEnrollment $e) => mb_strtolower(
                $e->user?->last_name ?: $e->user?->name ?: ''
            ))
            ->values();

        $completedAttempts = $enrollments
            ->map(fn (QuizEnrollment $e) => $e->quizAttempt)
            ->filter();

        $totalApproved   = $enrollments->whereIn('status', [
            QuizEnrollment::STATUS_APPROVED,
            QuizEnrollment::STATUS_COMPLETED,
        ])->count();

        $completed       = $completedAttempts->count();
        $notDoneYet      = max($totalApproved - $completed, 0);

        $averageScore = $completedAttempts->isNotEmpty()
            ? round($completedAttempts->avg(fn (QuizAttempt $a) => $a->percentage), 1)
            : null;

        $passes = $completedAttempts->filter(
            fn (QuizAttempt $a) => $this->isPassed($a, $quiz)
        )->count();

        $passRate = $completedAttempts->isNotEmpty()
            ? round(($passes / $completedAttempts->count()) * 100, 1)
            : null;

        return [
            'kpi' => [
                'total'         => $totalApproved,
                'completed'     => $completed,
                'pending'       => $notDoneYet,
                'average_score' => $averageScore,
                'pass_rate'     => $passRate,
            ],
            'enrollments' => $enrollments,
        ];
    }

    /**
     * "Promosso" se gli errori sono <= max_errors. Se total_questions è 0
     * non c'è esito sensato, ritorniamo false.
     */
    public function isPassed(QuizAttempt $attempt, Quiz $quiz): bool
    {
        if ($attempt->total_questions === 0) {
            return false;
        }

        $errors = $attempt->total_questions - $attempt->score;

        return $errors <= ($quiz->max_errors ?? 0);
    }
}
