<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Pagination\Paginator;
use App\Models\InstructorNote;
use App\Models\User;
use App\Models\Question;
use App\Models\Category;
use App\Models\Quiz;
use App\Models\AuditLog;
use App\Models\QuestionReport;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Cache;
use App\Observers\CategoryMaterialObserver;
use App\Observers\CategoryObserver;
use App\Observers\InstructorNoteObserver;
use App\Observers\QuestionObserver;
use App\Observers\QuizObserver;
use App\Observers\UserObserver;
use App\Listeners\SendBackupFailedNotification;
use Spatie\Backup\Events\BackupHasFailed;

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
        Paginator::useBootstrapFive();

        /*
        |--------------------------------------------------------------------------
        | OBSERVERS
        |--------------------------------------------------------------------------
        */

        Event::listen(BackupHasFailed::class, SendBackupFailedNotification::class);

        Quiz::observe(QuizObserver::class);
        Question::observe(QuestionObserver::class);
        Category::observe(CategoryObserver::class);
        User::observe(UserObserver::class);
        \App\Models\CategoryMaterial::observe(CategoryMaterialObserver::class);
        InstructorNote::observe(InstructorNoteObserver::class);

        /*
        |--------------------------------------------------------------------------
        | GATES MENU ADMINLTE
        |--------------------------------------------------------------------------
        */

        Gate::define('admin-only', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('content-editor', function (User $user) {
            return $user->isAdmin() || $user->isEditor();
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

        // 🔥 Voce sidebar "Segnalazioni": visibile a chi può moderare le domande
        // (admin via bypass, editor con permesso edit_question).
        Gate::define('view-question-reports', function (User $user) {
            return $user->canEditQuestion();
        });

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

        Gate::define('is-instructor', function (User $user) {
            return $user->isInstructor();
        });

        // Visibile sia all'istruttore che all'admin (supervisione)
        Gate::define('instructor-area', function (User $user) {
            return $user->isInstructor() || $user->isAdmin();
        });

        /*
        |--------------------------------------------------------------------------
        | VIEW COMPOSER ADMINLTE
        |--------------------------------------------------------------------------
        */

        // Composer mirato su layouts.admin per il badge "Ripasso intelligente" in sidebar.
        // NON aggiunto a '*' per non peggiorare il known issue esistente.
        View::composer('layouts.admin', function () {
            if (!auth()->check() || !auth()->user()->isViewer()) {
                return;
            }

            $dueToday = app(\App\Services\SpacedRepetitionService::class)
                ->getUpcomingCount(auth()->user())['due_today'];

            if ($dueToday === 0) {
                return;
            }

            config(['adminlte.menu' => collect(config('adminlte.menu'))->map(function ($item) use ($dueToday) {
                if (($item['key'] ?? '') === 'smart-review') {
                    $item['label']       = $dueToday;
                    $item['label_color'] = 'danger';
                }
                return $item;
            })->toArray()]);
        });

        // Badge sidebar: mirato su layouts.admin (non più su '*').
        // Chiude il known issue "View::composer('*') gira su ogni view".
        View::composer('layouts.admin', function () {
            if (!auth()->check()) {
                return;
            }

            $since = now()->subHour();

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
                    'pending_reports' => QuestionReport::pending()->count(),
                ];
            });

            config(['adminlte.menu' => collect(config('adminlte.menu'))->map(function ($item) use ($counts) {
                if (!isset($item['key'])) {
                    return $item;
                }

                switch ($item['key']) {
                    case 'questions':
                        if ($counts['questions'] > 0) {
                            $item['label']       = $counts['questions'];
                            $item['label_color'] = 'success';
                        }
                        break;
                    case 'categories':
                        if ($counts['categories'] > 0) {
                            $item['label']       = $counts['categories'];
                            $item['label_color'] = 'info';
                        }
                        break;
                    case 'users':
                        if ($counts['users'] > 0) {
                            $item['label']       = $counts['users'];
                            $item['label_color'] = 'primary';
                        }
                        break;
                    case 'quizzes':
                        if ($counts['quizzes'] > 0) {
                            $item['label']       = $counts['quizzes'];
                            $item['label_color'] = 'warning';
                        }
                        break;
                    case 'audit':
                        if ($counts['audit'] > 0) {
                            $item['label']       = $counts['audit'];
                            $item['label_color'] = 'danger';
                        }
                        break;
                    case 'registrations':
                        if ($counts['pending_registrations'] > 0) {
                            $item['label']       = $counts['pending_registrations'];
                            $item['label_color'] = 'warning';
                        }
                        break;
                    case 'question-reports':
                        if ($counts['pending_reports'] > 0) {
                            $item['label']       = $counts['pending_reports'];
                            $item['label_color'] = 'warning';
                        }
                        break;
                }

                return $item;
            })->toArray()]);
        });
    }
}
