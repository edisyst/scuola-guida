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

            /* 15.1: shell navy uniforme per tutti i ruoli.
               La classe role-{ruolo} sul body è l'unico discriminatore:
               CSS usa quella per stripe sidebar, voce attiva e badge. */
            $role = match (true) {
                $user->isAdmin()      => 'admin',
                $user->isEditor()     => 'editor',
                $user->isViewer()     => 'viewer',
                $user->isInstructor() => 'instructor',
                default               => 'viewer',
            };

            config([
                'adminlte.classes_body'    => "role-{$role}",
                'adminlte.classes_sidebar' => 'sidebar-dark-primary elevation-4',
                'adminlte.classes_topnav'  => 'navbar-dark sg-navbar',
            ]);
        }

        return $next($request);
    }
}
