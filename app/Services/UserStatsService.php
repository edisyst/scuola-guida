<?php

namespace App\Services;

use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class UserStatsService
{
    public const CACHE_TTL = 600; // 10 minuti

    public static function cacheKey(int $userId): string
    {
        return "user_stats_{$userId}";
    }

    public static function forget(int $userId): void
    {
        Cache::forget(self::cacheKey($userId));
    }

    public function get(User $user): array
    {
        return Cache::remember(self::cacheKey($user->id), self::CACHE_TTL, function () use ($user) {
            return $this->compute($user);
        });
    }

    private function compute(User $user): array
    {
        $base = QuizAttempt::where('user_id', $user->id);

        $totalAttempts = (clone $base)->count();

        if ($totalAttempts === 0) {
            return [
                'total_attempts'     => 0,
                'total_questions'    => 0,
                'total_correct'      => 0,
                'avg_percentage'     => 0.0,
                'best_percentage'    => 0.0,
                'worst_percentage'   => 0.0,
                'passed_count'       => 0,
                'failed_count'       => 0,
                'pass_rate'          => 0.0,
                'avg_duration'       => 0,
                'total_duration'     => 0,
                'last_attempt_at'    => null,
                'first_attempt_at'   => null,
                'latest_attempts'    => [],
                'daily_chart'        => [],
                'avg_by_quiz'        => [],
                'generated_at'       => now()->toIso8601String(),
            ];
        }

        $aggregate = (clone $base)
            ->selectRaw('SUM(score) as total_correct')
            ->selectRaw('SUM(total_questions) as total_questions')
            ->selectRaw('SUM(duration) as total_duration')
            ->selectRaw('AVG(duration) as avg_duration')
            ->selectRaw('MIN(created_at) as first_at')
            ->selectRaw('MAX(created_at) as last_at')
            ->first();

        $percentages = (clone $base)
            ->selectRaw('score, total_questions, (score * 100.0 / NULLIF(total_questions,0)) as pct')
            ->get();

        $avgPct   = round($percentages->avg('pct') ?? 0, 2);
        $bestPct  = round($percentages->max('pct') ?? 0, 2);
        $worstPct = round($percentages->min('pct') ?? 0, 2);

        $passed = $percentages->filter(fn ($r) => $r->pct >= 60)->count();
        $failed = $totalAttempts - $passed;
        $passRate = $totalAttempts > 0 ? round(($passed / $totalAttempts) * 100, 2) : 0;

        $latestAttempts = QuizAttempt::with('quiz:id,title')
            ->where('user_id', $user->id)
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn (QuizAttempt $a) => [
                'id'              => $a->id,
                'quiz_title'      => $a->quiz?->title ?? '—',
                'score'           => $a->score,
                'total_questions' => $a->total_questions,
                'percentage'      => $a->percentage,
                'is_passed'       => $a->is_passed,
                'duration'        => $a->duration,
                'created_at'      => $a->created_at?->toDateTimeString(),
            ])
            ->toArray();

        $dailyChart = (clone $base)
            ->where('created_at', '>=', now()->subDays(30))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as attempts'),
                DB::raw('AVG(score * 100.0 / NULLIF(total_questions,0)) as avg_pct')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($r) => [
                'date'     => $r->date,
                'attempts' => (int) $r->attempts,
                'avg_pct'  => round((float) $r->avg_pct, 2),
            ])
            ->toArray();

        $avgByQuiz = (clone $base)
            ->join('quizzes', 'quizzes.id', '=', 'quiz_attempts.quiz_id')
            ->select(
                'quizzes.id',
                'quizzes.title',
                DB::raw('COUNT(*) as attempts'),
                DB::raw('AVG(score * 100.0 / NULLIF(total_questions,0)) as avg_pct'),
                DB::raw('MAX(score * 100.0 / NULLIF(total_questions,0)) as best_pct')
            )
            ->groupBy('quizzes.id', 'quizzes.title')
            ->orderByDesc('attempts')
            ->limit(10)
            ->get()
            ->map(fn ($r) => [
                'id'       => $r->id,
                'title'    => $r->title,
                'attempts' => (int) $r->attempts,
                'avg_pct'  => round((float) $r->avg_pct, 2),
                'best_pct' => round((float) $r->best_pct, 2),
            ])
            ->toArray();

        return [
            'total_attempts'     => $totalAttempts,
            'total_questions'    => (int) $aggregate->total_questions,
            'total_correct'      => (int) $aggregate->total_correct,
            'avg_percentage'     => $avgPct,
            'best_percentage'    => $bestPct,
            'worst_percentage'   => $worstPct,
            'passed_count'       => $passed,
            'failed_count'       => $failed,
            'pass_rate'          => $passRate,
            'avg_duration'       => (int) round((float) $aggregate->avg_duration),
            'total_duration'     => (int) $aggregate->total_duration,
            'last_attempt_at'    => $aggregate->last_at,
            'first_attempt_at'   => $aggregate->first_at,
            'latest_attempts'    => $latestAttempts,
            'daily_chart'        => $dailyChart,
            'avg_by_quiz'        => $avgByQuiz,
            'generated_at'       => now()->toIso8601String(),
        ];
    }
}
