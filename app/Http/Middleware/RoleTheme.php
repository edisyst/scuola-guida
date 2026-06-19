<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleTheme
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $user = auth()->user();

            /* Skin sidebar costanti dal lotto 15.0: non più lette da system_settings.
               Il redesign sidebar avverrà nel lotto 15.1. */
            if ($user->isAdmin()) {
                config([
                    'adminlte.classes_body'    => 'role-admin',
                    'adminlte.classes_sidebar' => 'sidebar-dark-danger elevation-4',
                    'adminlte.classes_topnav'  => 'navbar-danger navbar-dark',
                ]);
            } elseif ($user->isEditor()) {
                config([
                    'adminlte.classes_body'    => 'role-editor',
                    'adminlte.classes_sidebar' => 'sidebar-dark-primary elevation-4',
                    'adminlte.classes_topnav'  => 'navbar-info navbar-dark',
                ]);
            } elseif ($user->isViewer()) {
                config([
                    'adminlte.classes_body'    => 'role-viewer',
                    'adminlte.classes_sidebar' => 'sidebar-dark-warning elevation-4',
                    'adminlte.classes_topnav'  => 'navbar-warning navbar-light',
                ]);
            } elseif ($user->isInstructor()) {
                config([
                    'adminlte.classes_body'    => 'role-instructor',
                    'adminlte.classes_sidebar' => 'sidebar-dark-success elevation-4',
                    'adminlte.classes_topnav'  => 'navbar-success navbar-dark',
                ]);
            }
        }

        return $next($request);
    }
}
