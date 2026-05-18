<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\DashboardStatsService;
use App\Services\UserStatsService;
use Illuminate\Http\Request;

class UserStatsController extends Controller
{
    public function __construct(
        private readonly UserStatsService $service,
        private readonly DashboardStatsService $dashboardStats,
    ) {}

    /**
     * Dashboard personale dell'utente autenticato.
     * Admin ed editor vedono i KPI globali; viewer vede le proprie statistiche.
     */
    public function me()
    {
        $user = auth()->user();

        if ($user->isAdmin() || $user->isEditor()) {
            return view('admin.dashboard', [
                'stats'          => $this->dashboardStats->kpi(),
                'questionsChart' => $this->dashboardStats->dailyCreated(\App\Models\Question::class),
                'usersChart'     => $this->dashboardStats->dailyCreated(\App\Models\User::class),
            ]);
        }

        return view('stats.dashboard', [
            'user'        => $user,
            'stats'       => $this->service->get($user),
            'isAdminView' => false,
        ]);
    }

    /**
     * Visualizzazione admin: statistiche di un utente specifico.
     */
    public function show(User $user)
    {
        abort_unless(auth()->user()->canEditUser() || auth()->user()->isAdmin(), 403);

        $stats = $this->service->get($user);

        return view('stats.dashboard', [
            'user'  => $user,
            'stats' => $stats,
            'isAdminView' => true,
        ]);
    }

    /**
     * Forza il rigeneramento della cache stats.
     */
    public function refresh(Request $request, User $user)
    {
        $isSelf = auth()->id() === $user->id;
        $canAdmin = auth()->user()->isAdmin() || auth()->user()->canEditUser();

        abort_unless($isSelf || $canAdmin, 403);

        UserStatsService::forget($user->id);

        $back = $request->boolean('as_admin')
            ? route('admin.users.stats', $user)
            : ($isSelf ? route('dashboard') : route('admin.users.stats', $user));

        return redirect($back)->with('success', 'Statistiche aggiornate');
    }
}
