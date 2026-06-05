<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireLicenseType
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        if (!$user->isViewer()) {
            return $next($request);
        }

        if ($user->getActiveLicenseType() === null) {
            return redirect()->route('profile.edit')
                ->with('warning', __('flash.license_type_required'));
        }

        return $next($request);
    }
}
