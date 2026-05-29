<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardStatsService
{
    private const KPI_CACHE_TTL   = 300;
    private const CHART_CACHE_TTL = 900;

    public function kpi(): array
    {
        return Cache::remember('dashboard_kpi', self::KPI_CACHE_TTL, fn () => [
            'users'      => User::count(),
            'questions'  => Question::count(),
            'categories' => Category::count(),
            'quizzes'    => Quiz::count(),
        ]);
    }

    /**
     * Aggregato per giorno di creazione (ultimi $days giorni).
     * Cache time-based (TTL 900s): dati storici, nessuna invalidation hard.
     */
    public function dailyCreated(string $modelClass, int $days = 30, int $limit = 30)
    {
        $cacheKey = 'daily_chart_' . strtolower(class_basename($modelClass)) . "_{$days}_{$limit}";

        return Cache::remember($cacheKey, self::CHART_CACHE_TTL, function () use ($modelClass, $days, $limit) {
            return $modelClass::select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('count(*) as total')
                )
                ->where('created_at', '>=', now()->subDays($days))
                ->groupBy('date')
                ->orderBy('date')
                ->limit($limit)
                ->get();
        });
    }
}
