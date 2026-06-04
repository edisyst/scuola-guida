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
        $locale    = $request->session()->get('app_locale', config('locales.default', 'it'));

        App::setLocale(in_array($locale, $supported) ? $locale : config('locales.default', 'it'));

        return $next($request);
    }
}
