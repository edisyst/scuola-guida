<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();

        if (!$user) {
            abort(403);
        }

        // dd($user->role, $roles);

        if (!in_array($user->role, $roles)) {
            abort(403, 'Non autorizzato da RoleMiddleware');
        }

        return $next($request);
    }
}
