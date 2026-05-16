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
        // hasPermission() gestisce le regole hardcoded:
        //   read_*  → true per tutti gli utenti autenticati
        //   bulk_*  → true solo per admin
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

        // 🔥 Menu iscrizioni quiz lato viewer (l'editor è escluso dalla feature)
        Gate::define('viewer-quiz-area', function (User $user) {
            return $user->isViewer() || $user->isAdmin();
        });

        /*
        |--------------------------------------------------------------------------
        | VIEW COMPOSER ADMINLTE
        |--------------------------------------------------------------------------
        */

        View::composer('*', function () {
            // 🔥 cache unica per tutti i badge (più efficiente)
            $counts = Cache::remember('admin_badges', 60, function () {
                return [
                    'users' => User::count(),
                    'questions' => Question::count(),
                    'categories' => Category::count(),
                    'quizzes' => Quiz::count(),
                    'audit' => AuditLog::count(),
                    'pending_registrations' => User::where('role', User::ROLE_VIEWER)
                        ->where('registration_status', User::REG_PENDING)
                        ->count(),
                ];
            });

            config(['adminlte.menu' => collect(config('adminlte.menu'))->map(function ($item) use ($counts) {

                if (!isset($item['key']))
                    return $item;

                switch ($item['key']) {
                    case 'questions':
                        $item['label'] = $counts['questions'];
                        $item['label_color'] = 'success';
                        break;

                    case 'categories':
                        $item['label'] = $counts['categories'];
                        $item['label_color'] = 'info';
                        break;

                    case 'users':
                        $item['label'] = $counts['users'];
                        $item['label_color'] = 'primary';
                        break;

                    case 'quizzes':
                        $item['label'] = $counts['quizzes'];
                        $item['label_color'] = 'warning';
                        break;

                    case 'audit':
                        $item['label'] = $counts['audit'];
                        $item['label_color'] = 'danger';
                        break;

                    case 'registrations':
                        if ($counts['pending_registrations'] > 0) {
                            $item['label'] = $counts['pending_registrations'];
                            $item['label_color'] = 'warning';
                        }
                        break;
                }

                    return $item;
                })->toArray()
            ]);
        });
    }
}
