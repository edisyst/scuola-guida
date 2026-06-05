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

        // Per i loggati: users.locale ha priorità (fonte di verità persistita).
        // Per gli ospiti: sessione/cookie. LocaleController::switch() aggiorna entrambi.
        $locale = ($request->user()?->locale)
            ?? $request->session()->get('app_locale')
            ?? $default;

        App::setLocale(in_array($locale, $supported) ? $locale : $default);

        return $next($request);
    }
}
