<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;
use App\Models\Question;
use App\Models\Category;
use App\Models\Quiz;
use App\Models\AuditLog;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Cache;
use App\Observers\CategoryObserver;
use App\Observers\QuestionObserver;
use App\Observers\QuizObserver;
use App\Observers\UserObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /*
        |--------------------------------------------------------------------------
        | OBSERVERS
        |--------------------------------------------------------------------------
        */

        Quiz::observe(QuizObserver::class);
        Question::observe(QuestionObserver::class);
        Category::observe(CategoryObserver::class);
        User::observe(UserObserver::class);

        /*
        |--------------------------------------------------------------------------
        | GATES MENU ADMINLTE
        |--------------------------------------------------------------------------
        */

        Gate::define('admin-only', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('view-admin', function (User $user) {
            return in_array($user->role, ['admin', 'editor', 'viewer']);
        });

        Gate::define('manage-questions', function (User $user) {
            return in_array($user->role, ['admin', 'editor', 'viewer']);
        });

//      Gate::define('create-question', fn($user) => $user->canCreateQuestion()); // provare a vedere se è lo stesso
        Gate::define('create-question', function (User $user) {
            return $user->canCreateQuestion(); // nel menu metterò 'can' => 'create-question'
        });

//      Gate::define('delete-question', fn($user) => $user->canDeleteQuestion());
        Gate::define('delete-question', function (User $user) {
            return $user->canDeleteQuestion();
        });

        // Gates dinamiche per ogni combinazione action_entity.
        // Tutti i permessi (incluso read_* e bulk_*) sono configurabili per ruolo
        // dal pannello /admin/roles e risolti da hasPermission().
        foreach (User::ACTIONS as $action) {
            foreach (User::ENTITIES as $entity) {
                $perm = "{$action}_{$entity}";
                Gate::define($perm, fn(User $user) => $user->hasPermission($perm));
            }
        }

        // 🔥 Menu Users: visibile se l'utente può fare qualcosa sugli utenti
        Gate::define('manage-users-menu', function (User $user) {
            return $user->isAdmin()
                || $user->canCreateUser()
                || $user->canEditUser()
                || $user->canDeleteUser()
                || $user->canManageUser();
        });

        // 🔥 Catalogo "Quiz disponibili": viewer (partecipa), admin/editor (sola lettura).
        Gate::define('viewer-quiz-area', function (User $user) {
            return $user->isViewer() || $user->isAdmin() || $user->isEditor();
        });

        // 🔥 Aree riservate a chi partecipa effettivamente agli esami ufficiali:
        // "Le mie iscrizioni" e "I miei tentativi" sono dati personali del viewer.
        // Admin/editor non sostengono esami → menu nascosto.
        Gate::define('exam-participant', function (User $user) {
            return $user->isViewer();
        });

        /*
        |--------------------------------------------------------------------------
        | VIEW COMPOSER ADMINLTE
        |--------------------------------------------------------------------------
        */

        View::composer('*', function () {
            // I badge in sidebar mostrano solo gli elementi aggiunti nell'ultima ora.
            $since = now()->subHour();

            // 🔥 cache unica per tutti i badge (più efficiente)
            $counts = Cache::remember('admin_badges', 60, function () use ($since) {
                return [
                    'users'      => User::where('created_at', '>=', $since)->count(),
                    'questions'  => Question::where('created_at', '>=', $since)->count(),
                    'categories' => Category::where('created_at', '>=', $since)->count(),
                    'quizzes'    => Quiz::where('created_at', '>=', $since)->count(),
                    'audit'      => AuditLog::where('created_at', '>=', $since)->count(),
                    'pending_registrations' => User::where('role', User::ROLE_VIEWER)
                        ->where('registration_status', User::REG_PENDING)
                        ->where('registration_submitted_at', '>=', $since)
                        ->count(),
                ];
            });

            // Conteggio non-lette per l'utente corrente (non cacheato: per-utente).
            // Anche qui filtriamo all'ultima ora per coerenza con gli altri badge.
            $unreadNotifications = auth()->check()
                ? auth()->user()->unreadNotifications()
                    ->where('created_at', '>=', $since)
                    ->count()
                : 0;

            config(['adminlte.menu' => collect(config('adminlte.menu'))->map(function ($item) use ($counts, $unreadNotifications) {

                if (!isset($item['key']))
                    return $item;

                switch ($item['key']) {
                    case 'questions':
                        if ($counts['questions'] > 0) {
                            $item['label'] = $counts['questions'];
                            $item['label_color'] = 'success';
                        }
                        break;

                    case 'categories':
                        if ($counts['categories'] > 0) {
                            $item['label'] = $counts['categories'];
                            $item['label_color'] = 'info';
                        }
                        break;

                    case 'users':
                        if ($counts['users'] > 0) {
                            $item['label'] = $counts['users'];
                            $item['label_color'] = 'primary';
                        }
                        break;

                    case 'quizzes':
                        if ($counts['quizzes'] > 0) {
                            $item['label'] = $counts['quizzes'];
                            $item['label_color'] = 'warning';
                        }
                        break;

                    case 'audit':
                        if ($counts['audit'] > 0) {
                            $item['label'] = $counts['audit'];
                            $item['label_color'] = 'danger';
                        }
                        break;

                    case 'registrations':
                        if ($counts['pending_registrations'] > 0) {
                            $item['label'] = $counts['pending_registrations'];
                            $item['label_color'] = 'warning';
                        }
                        break;

                    case 'notifications':
                        if ($unreadNotifications > 0) {
                            $item['label'] = $unreadNotifications;
                        }
                        break;
                }

                    return $item;
                })->toArray()
            ]);
        });
    }
}
