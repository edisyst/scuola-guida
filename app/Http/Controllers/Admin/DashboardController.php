<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Question;
use App\Models\Category;
use App\Models\Quiz;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // 🔥 KPI base
        $stats = [
            'users' => User::count(),
            'questions' => Question::count(),
            'categories' => Category::count(),
            'quizzes' => Quiz::count(),
        ];

        // 📈 domande per giorno
        $questionsChart = Question::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('count(*) as total')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->where('created_at', '>=', now()->subDays(30))
            ->limit(30)
            ->get();

        // 📈 utenti per giorno
        $usersChart = User::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('count(*) as total')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->where('created_at', '>=', now()->subDays(30))
            ->limit(30)
            ->get();

        return view('admin.dashboard', compact(
            'stats',
            'questionsChart',
            'usersChart'
        ));
    }
}
