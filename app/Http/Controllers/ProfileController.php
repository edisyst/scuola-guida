<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\GdprExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    public function downloadPersonalData(GdprExportService $service): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $user = auth()->user();

        $zipPath = $service->generateZip($user);

        AuditLog::create([
            'user_id'    => $user->id,
            'event'      => 'gdpr_export',
            'model_type' => User::class,
            'model_id'   => $user->id,
            'old_values' => [],
            'new_values' => ['exported_by' => $user->id, 'exported_at' => now()->toIso8601String()],
        ]);

        return response()
            ->download($zipPath, "miei-dati-{$user->id}.zip")
            ->deleteFileAfterSend(true);
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
