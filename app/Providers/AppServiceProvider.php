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
use App\Observers\QuizObserver;

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
            return $user->canEditQuestion() || $user->canDeleteQuestion();
        });

//      Gate::define('create-question', fn($user) => $user->canCreateQuestion()); // provare a vedere se è lo stesso
        Gate::define('create-question', function (User $user) {
            return $user->canCreateQuestion(); // nel menu metterò 'can' => 'create-question'
        });

//      Gate::define('delete-question', fn($user) => $user->canDeleteQuestion());
        Gate::define('delete-question', function (User $user) {
            return $user->canDeleteQuestion();
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
                }

                    return $item;
                })->toArray()
            ]);
        });
    }
}
