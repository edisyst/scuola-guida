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

            if ($user->isAdmin()) {
                config([
                    'adminlte.classes_body'    => 'role-admin',
                    'adminlte.classes_sidebar' => setting('appearance.sidebar_skin_admin', 'sidebar-dark-danger') . ' elevation-4',
                    'adminlte.classes_topnav'  => 'navbar-danger navbar-dark',
                ]);
            } elseif ($user->isEditor()) {
                config([
                    'adminlte.classes_body'    => 'role-editor',
                    'adminlte.classes_sidebar' => setting('appearance.sidebar_skin_editor', 'sidebar-dark-primary') . ' elevation-4',
                    'adminlte.classes_topnav'  => 'navbar-info navbar-dark',
                ]);
            } elseif ($user->isViewer()) {
                config([
                    'adminlte.classes_body'    => 'role-viewer',
                    'adminlte.classes_sidebar' => setting('appearance.sidebar_skin_viewer', 'sidebar-dark-warning') . ' elevation-4',
                    'adminlte.classes_topnav'  => 'navbar-warning navbar-light',
                ]);
            } elseif ($user->isInstructor()) {
                config([
                    'adminlte.classes_body'    => 'role-instructor',
                    'adminlte.classes_sidebar' => setting('appearance.sidebar_skin_instructor', 'sidebar-dark-success') . ' elevation-4',
                    'adminlte.classes_topnav'  => 'navbar-success navbar-dark',
                ]);
            }
        }

        return $next($request);
    }
}
