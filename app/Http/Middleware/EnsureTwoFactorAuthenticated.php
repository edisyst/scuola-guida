<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if (! $user->requiresTwoFactor()) {
            return $next($request);
        }

        if (! $user->hasTwoFactorEnabled()) {
            return redirect()->route('2fa.setup.show')
                ->with('warning', "Devi configurare il 2FA prima di accedere all'area admin.");
        }

        if (! $request->session()->get('2fa_verified')) {
            return redirect()->route('2fa.challenge.show');
        }

        return $next($request);
    }
}
