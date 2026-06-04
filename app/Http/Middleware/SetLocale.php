<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = array_keys(config('locales.supported', []));
        $default   = config('locales.default', 'it');

        // Priorità: sessione (click bandierina) → users.locale (Feature 7.1) → default.
        // Non scrive in sessione: solo LocaleController::switch() lo fa esplicitamente.
        $locale = $request->session()->get('app_locale')
            ?? ($request->user()?->locale)
            ?? $default;

        App::setLocale(in_array($locale, $supported) ? $locale : $default);

        return $next($request);
    }
}
