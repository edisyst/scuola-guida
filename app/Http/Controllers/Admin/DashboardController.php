<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\User;
use App\Services\DashboardStatsService;
use App\Services\EditorMetricsService;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardStatsService $stats,
        private EditorMetricsService $metrics,
    ) {}

    public function index()
    {
        return view('admin.dashboard', [
            'stats'          => $this->stats->kpi(),
            'questionsChart' => $this->stats->dailyCreated(Question::class),
            'usersChart'     => $this->stats->dailyCreated(User::class),
            'globalMetrics'  => $this->metrics->getGlobalContentMetrics(),
        ]);
    }
}
