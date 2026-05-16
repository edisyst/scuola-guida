<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserRegistrationService;
use Illuminate\Http\Request;
use RuntimeException;

class RegistrationController extends Controller
{
    public function __construct(private UserRegistrationService $service) {}

    /**
     * Elenco delle richieste di iscrizione anagrafica, filtrabili per stato.
     * Mostra di default le pendenti.
     */
    public function index(Request $request)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $status = $request->query('status', User::REG_PENDING);

        $valid = array_keys(User::REG_STATUSES);
        if (!in_array($status, $valid, true)) {
            $status = null;
        }

        $registrations = User::query()
            ->where('role', User::ROLE_VIEWER)
            ->when($status, fn ($q) => $q->where('registration_status', $status))
            ->when(
                !$status,
                fn ($q) => $q->whereIn('registration_status', [
                    User::REG_PENDING,
                    User::REG_APPROVED,
                    User::REG_REJECTED,
                ]),
            )
            ->with('registrationReviewer')
            ->latest('registration_submitted_at')
            ->paginate(20)
            ->withQueryString();

        $pendingCount = User::where('role', User::ROLE_VIEWER)
            ->where('registration_status', User::REG_PENDING)
            ->count();

        return view('admin.registrations.index', compact('registrations', 'status', 'pendingCount'));
    }

    public function show(User $user)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        if (!$user->isViewer() || !$user->hasSubmittedRegistration()) {
            abort(404);
        }

        $user->load('registrationReviewer');
        $documentUrl = $this->service->documentUrl($user);

        return view('admin.registrations.show', compact('user', 'documentUrl'));
    }

    public function approve(User $user)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        try {
            $this->service->approve($user, auth()->user());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.registrations.index')
            ->with('success', 'Iscrizione approvata per ' . $user->fullAnagraphicName() . '.');
    }

    public function reject(Request $request, User $user)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->service->reject($user, auth()->user(), $data['reason'] ?? null);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.registrations.index')
            ->with('success', 'Iscrizione rifiutata per ' . $user->fullAnagraphicName() . '.');
    }
}
