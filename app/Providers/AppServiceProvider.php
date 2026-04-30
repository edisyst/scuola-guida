<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;
use App\Models\Question;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Cache;

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

        View::composer('*', function ($view) {

            config([
                'adminlte.menu' => collect(config('adminlte.menu'))->map(function ($item) {

                    if (($item['key'] ?? '') === 'questions_menu') {
                        $item['label'] = Cache::remember('questions_count', 60, function () {
                            return Question::count();
                        });
                        $item['label_color'] = 'success';
                    }

                    return $item;

                })->toArray()
            ]);
        });
    }
}
