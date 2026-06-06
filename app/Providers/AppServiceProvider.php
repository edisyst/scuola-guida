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
use App\Models\LicenseType;
use App\Models\AuditLog;
use App\Models\QuestionReport;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Cache;
use App\Observers\CategoryMaterialObserver;
use App\Observers\CategoryObserver;
use App\Observers\InstructorNoteObserver;
use App\Observers\QuestionObserver;
use App\Observers\CategoryTranslationObserver;
use App\Observers\QuestionTranslationObserver;
use App\Observers\QuizObserver;
use App\Observers\UserObserver;
use App\Observers\LicenseTypeObserver;
use App\Observers\DrivingModuleObserver;
use App\Observers\DrivingSessionObserver;
use App\Models\DrivingModule;
use App\Models\DrivingSession;
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
        LicenseType::observe(LicenseTypeObserver::class);
        \App\Models\CategoryMaterial::observe(CategoryMaterialObserver::class);
        InstructorNote::observe(InstructorNoteObserver::class);
        \App\Models\QuestionTranslation::observe(QuestionTranslationObserver::class);
        \App\Models\CategoryTranslation::observe(CategoryTranslationObserver::class);
        DrivingModule::observe(DrivingModuleObserver::class);
        DrivingSession::observe(DrivingSessionObserver::class);

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
                    $item['label_color'] = 'light';
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

            // Conta gli elementi recenti per una voce in base al suo 'key'.
            $badgeCount = function (array $item) use ($counts) {
                return match ($item['key'] ?? null) {
                    'questions'        => $counts['questions'],
                    'categories'       => $counts['categories'],
                    'users'            => $counts['users'],
                    'quizzes'          => $counts['quizzes'],
                    'audit'            => $counts['audit'],
                    'registrations'    => $counts['pending_registrations'],
                    'question-reports' => $counts['pending_reports'],
                    default            => 0,
                };
            };

            // Applica il badge a una voce con il colore indicato (se conteggio > 0).
            $applyBadge = function (array $item, string $color) use ($badgeCount) {
                $count = $badgeCount($item);
                if ($count > 0) {
                    $item['label']       = $count;
                    $item['label_color'] = $color;
                }
                return $item;
            };

            // Mappa il menu:
            //  - dropdown della barra superiore → badge sempre ROSSI sulle voci
            //    figlie + counter aggregato (rosso) sul toggle, visibile col
            //    dropdown chiuso;
            //  - voci della sidebar → badge BIANCHI ('light').
            config(['adminlte.menu' => collect(config('adminlte.menu'))->map(function ($item) use ($applyBadge) {
                if (!empty($item['submenu'])) {
                    $sum = 0;
                    $item['submenu'] = array_map(function ($child) use ($applyBadge, &$sum) {
                        $child = $applyBadge($child, 'danger');
                        if (isset($child['label'])) {
                            $sum += (int) $child['label'];
                        }
                        return $child;
                    }, $item['submenu']);

                    if ($sum > 0) {
                        $item['label']       = $sum;
                        $item['label_color'] = 'danger';
                    }

                    return $item;
                }

                return $applyBadge($item, 'light');
            })->toArray()]);
        });
    }
}
