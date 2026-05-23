<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorChallengeController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        if ($request->session()->get('2fa_verified')) {
            return redirect()->intended(route('dashboard'));
        }

        return view('auth.two-factor-challenge');
    }

    public function verify(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($request->filled('recovery_code')) {
            return $this->verifyRecoveryCode($request, $user);
        }

        $request->validate(['code' => 'required|string']);

        $google2fa = new Google2FA();
        $valid = $google2fa->verifyKey(
            $user->two_factor_secret,
            $request->input('code', '')
        );

        if (! $valid) {
            return back()->withErrors(['code' => 'Il codice OTP non è valido. Riprova.']);
        }

        $request->session()->put('2fa_verified', true);

        return redirect()->intended(route('dashboard'));
    }

    private function verifyRecoveryCode(Request $request, User $user): RedirectResponse
    {
        $request->validate(['recovery_code' => 'required|string']);

        $input = trim($request->input('recovery_code'));
        $codes = $user->two_factor_recovery_codes ?? [];

        $found = false;
        $remaining = [];

        foreach ($codes as $code) {
            if (! $found && hash_equals($code, $input)) {
                $found = true;
            } else {
                $remaining[] = $code;
            }
        }

        if (! $found) {
            return back()->withErrors(['recovery_code' => 'Il codice di recupero non è valido.']);
        }

        $user->two_factor_recovery_codes = $remaining;
        $user->save();

        $request->session()->put('2fa_verified', true);

        return redirect()->intended(route('dashboard'));
    }
}
