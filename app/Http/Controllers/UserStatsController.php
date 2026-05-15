<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\UserStatsService;
use Illuminate\Http\Request;

class UserStatsController extends Controller
{
    public function __construct(private readonly UserStatsService $service)
    {
    }

    /**
     * Dashboard personale dell'utente autenticato.
     */
    public function me()
    {
        $user  = auth()->user();
        $stats = $this->service->get($user);

        return view('stats.dashboard', [
            'user'  => $user,
            'stats' => $stats,
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
