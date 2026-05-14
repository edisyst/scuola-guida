<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardStatsService
{
    public function kpi(): array
    {
        return [
            'users'      => User::count(),
            'questions'  => Question::count(),
            'categories' => Category::count(),
            'quizzes'    => Quiz::count(),
        ];
    }

    /**
     * Aggregato per giorno di creazione (ultimi 30 giorni).
     */
    public function dailyCreated(string $modelClass, int $days = 30, int $limit = 30)
    {
        return $modelClass::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('count(*) as total')
            )
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('date')
            ->orderBy('date')
            ->limit($limit)
            ->get();
    }
}
