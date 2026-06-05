<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Requests\UpdateAccessibilityPreferencesRequest;
use App\Http\Requests\UpdateActiveLicenseTypeRequest;
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

    public function updateAccessibility(UpdateAccessibilityPreferencesRequest $request): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user->isViewer(), 403);

        $user->tts_enabled  = $request->boolean('tts_enabled');
        $user->tts_autoplay = $request->boolean('tts_autoplay');
        $user->save();

        return Redirect::route('profile.edit')
            ->with('success', __('flash.accessibility_updated'));
    }

    public function updateActiveLicenseType(UpdateActiveLicenseTypeRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        return Redirect::route('profile.edit')
            ->with('success', __('flash.license_type_updated'));
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
