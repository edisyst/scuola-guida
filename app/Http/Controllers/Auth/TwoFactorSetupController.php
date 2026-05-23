<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorSetupController extends Controller
{
    public function show(Request $request): View
    {
        $secret = $request->session()->get('2fa_setup_secret');

        if (! $secret) {
            $secret = (new Google2FA())->generateSecretKey();
            $request->session()->put('2fa_setup_secret', $secret);
        }

        $qrCodeUrl = (new Google2FA())->getQRCodeUrl(
            config('app.name'),
            $request->user()->email,
            $secret
        );

        $qrSvg = $this->renderQrSvg($qrCodeUrl);

        return view('auth.two-factor-setup', compact('secret', 'qrSvg'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['code' => 'required|digits:6']);

        $secret = $request->session()->get('2fa_setup_secret');

        if (! $secret) {
            return back()->withErrors(['code' => 'Sessione scaduta. Ricarica la pagina e riprova.']);
        }

        $valid = (new Google2FA())->verifyKey($secret, $request->input('code'));

        if (! $valid) {
            return back()->withErrors(['code' => "Il codice OTP non è valido. Verifica che l'orario del dispositivo sia corretto."]);
        }

        /** @var User $user */
        $user = $request->user();
        $codes = $user->generateRecoveryCodes();

        $user->two_factor_secret = $secret;
        $user->two_factor_enabled_at = now();
        $user->two_factor_recovery_codes = $codes;
        $user->save();

        $request->session()->forget('2fa_setup_secret');
        $request->session()->put('2fa_new_codes', $codes);

        return redirect()->route('2fa.codes.show');
    }

    public function showCodes(Request $request): View|RedirectResponse
    {
        $codes = $request->session()->get('2fa_new_codes');

        if (! $codes) {
            return redirect()->route('profile.edit');
        }

        return view('auth.two-factor-codes', compact('codes'));
    }

    public function confirmCodes(Request $request): RedirectResponse
    {
        $request->session()->forget('2fa_new_codes');
        $request->session()->put('2fa_verified', true);

        return redirect()->intended(route('profile.edit'))
            ->with('success', '2FA abilitato con successo. Tieni i codici di recupero al sicuro.');
    }

    public function disable(Request $request): RedirectResponse
    {
        $request->validateWithBag('twoFactorDisable', [
            'password' => ['required', 'current_password'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $user->two_factor_secret = null;
        $user->two_factor_enabled_at = null;
        $user->two_factor_recovery_codes = null;
        $user->save();

        $request->session()->forget('2fa_verified');

        return redirect()->route('profile.edit')
            ->with('success', '2FA disabilitato con successo.');
    }

    public function regenerateCodes(Request $request): RedirectResponse
    {
        $request->validateWithBag('twoFactorRegenerate', [
            'password' => ['required', 'current_password'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $codes = $user->generateRecoveryCodes();
        $user->two_factor_recovery_codes = $codes;
        $user->save();

        $request->session()->put('2fa_new_codes', $codes);

        return redirect()->route('2fa.codes.show');
    }

    private function renderQrSvg(string $url): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd()
        );

        return (new Writer($renderer))->writeString($url);
    }
}
