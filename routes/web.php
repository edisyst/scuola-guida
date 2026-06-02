<?php

use App\Http\Controllers\BookmarkController;
use App\Http\Controllers\ReviewErrorsController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\QuizAttemptController;
use App\Http\Controllers\QuizEnrollmentController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SimulatorController;
use App\Http\Controllers\StudyController;
use App\Http\Controllers\UserStatsController;
use App\Http\Controllers\Admin\CategoryMaterialController;
use App\Http\Controllers\Admin\QuestionReportController;
use App\Http\Controllers\Admin\RolePermissionController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\RegistrationController as AdminRegistrationController;
use App\Http\Controllers\Admin\CommandController as AdminCommandController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\HealthController;
use App\Http\Controllers\Admin\InstructorAssignmentController;
use App\Http\Controllers\Editor\EditorDashboardController;
use App\Http\Controllers\Instructor\InstructorController;
use App\Http\Controllers\Viewer\ProfileBadgesController;
use App\Http\Controllers\Viewer\StudyPlanController;
use App\Http\Controllers\Viewer\SmartReviewController;
use App\Http\Controllers\Auth\TwoFactorChallengeController;
use App\Http\Controllers\Auth\TwoFactorSetupController;
use App\Http\Controllers\Api\OfflineController;
use App\Http\Controllers\PushSubscriptionController;
// PWA offline fallback — no auth required (served from SW cache)
Route::get('/offline', fn() => view('offline'))->name('offline');

Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| AUTH AREA — tutti gli utenti autenticati
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {

    Route::get('/search', [SearchController::class, 'index'])->name('search');

    // Web Push subscriptions (viewer only — autorizzazione nel controller)
    Route::post('/push-subscriptions', [PushSubscriptionController::class, 'store'])
        ->name('push-subscriptions.store');
    Route::delete('/push-subscriptions', [PushSubscriptionController::class, 'destroy'])
        ->name('push-subscriptions.destroy');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Iscrizione anagrafica viewer (invio dati per esami ufficiali)
    Route::post('/profile/registration', [RegistrationController::class, 'submit'])
        ->name('profile.registration.submit');

    // Quiz: gioca (viewer / user)
    Route::get('quiz/{quiz}/play', [QuizController::class, 'play'])->name('quiz.play');

    // Quiz confermati e iscrizioni (viewer)
    Route::get('quiz/confirmed', [QuizEnrollmentController::class, 'catalog'])
        ->name('quiz.confirmed.index');
    Route::post('quiz/{quiz}/enrollments', [QuizEnrollmentController::class, 'store'])
        ->name('quiz.enrollments.store');
    Route::get('quiz/enrollments', [QuizEnrollmentController::class, 'myEnrollments'])
        ->name('quiz.enrollments.mine');

    // Dashboard personale utente
    Route::get('dashboard', [UserStatsController::class, 'me'])->name('dashboard');
    Route::post('dashboard/{user}/refresh', [UserStatsController::class, 'refresh'])
        ->name('dashboard.refresh');

    // Storico tentativi — solo i propri
    Route::get('quiz/attempts', [QuizAttemptController::class, 'index'])->name('quiz.attempts.index');
    Route::post('quiz/attempts', [QuizAttemptController::class, 'store'])->name('quiz.attempts.store');
    Route::get('quiz/attempts/{attempt}', [QuizAttemptController::class, 'show'])->name('quiz.attempts.show');
    Route::put('quiz/attempts/{attempt}', [QuizAttemptController::class, 'update'])->name('quiz.attempts.update');

    // Modalità Studio — allenamento libero senza timer/punteggio
    Route::prefix('study')->name('study.')->group(function () {
        Route::get('/', [StudyController::class, 'index'])->name('index');
        Route::post('/start', [StudyController::class, 'start'])->name('start');
        Route::get('/play', [StudyController::class, 'play'])->name('play');
        Route::post('/flag/{question}', [StudyController::class, 'flag'])->name('flag');
        Route::get('/summary', [StudyController::class, 'summary'])->name('summary');
        Route::delete('/session', [StudyController::class, 'destroy'])->name('destroy');
    });

    // Simulatore esame teorico patente B (allenamento individuale)
    Route::prefix('simulator')->name('simulator.')->group(function () {
        Route::get('/',                    [SimulatorController::class, 'index'])->name('index');
        Route::post('/start',              [SimulatorController::class, 'start'])->name('start');
        Route::get('/play',                [SimulatorController::class, 'play'])->name('play');
        Route::put('/{attempt}/autosave',  [SimulatorController::class, 'autosave'])->name('autosave');
        Route::post('/submit',             [SimulatorController::class, 'submit'])->name('submit');
        Route::get('/result/{attempt}',    [SimulatorController::class, 'result'])->name('result');
        Route::delete('/session',          [SimulatorController::class, 'destroy'])->name('destroy');
    });

    // Revisione errori aggregata personale (solo viewer)
    Route::prefix('review-errors')->name('viewer.review-errors.')->group(function () {
        Route::get('/', [ReviewErrorsController::class, 'index'])->name('index');
        Route::post('/{question}/learned', [ReviewErrorsController::class, 'markLearned'])->name('learned.store');
        Route::delete('/{question}/learned', [ReviewErrorsController::class, 'unmarkLearned'])->name('learned.destroy');
    });

    // Test diagnostico e piano di studio (viewer)
    Route::get('/diagnostic', [StudyPlanController::class, 'startDiagnostic'])
        ->name('viewer.diagnostic.show');
    Route::get('/study-plan', [StudyPlanController::class, 'show'])
        ->name('viewer.study-plan.show');

    // Badge e streak personali (viewer)
    Route::get('/profile/badges', [ProfileBadgesController::class, 'index'])
        ->name('viewer.profile.badges');

    // Ripasso intelligente — spaced repetition (viewer)
    Route::prefix('smart-review')->name('viewer.smart-review.')->group(function () {
        Route::get('/',        [SmartReviewController::class, 'index'])->name('index');
        Route::get('/session', [SmartReviewController::class, 'session'])->name('session');
    });

    // Domande salvate (bookmark persistenti viewer)
    Route::prefix('bookmarks')->name('bookmarks.')->group(function () {
        Route::get('/',              [BookmarkController::class, 'index'])->name('index');
        Route::delete('/{question}', [BookmarkController::class, 'destroy'])->name('destroy');
    });

    // Calendario sessioni d'esame (viewer)
    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');

    // Notifiche in-app (database notifications)
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/',        [NotificationController::class, 'index'])->name('index');
        Route::delete('/',     [NotificationController::class, 'destroyAll'])->name('destroyAll');
        Route::delete('/{id}', [NotificationController::class, 'destroy'])->name('destroy');
    });

    // PWA offline sync API (viewer only, JSON)
    Route::prefix('api/offline')->name('api.offline.')->group(function () {
        Route::get('/questions',    [OfflineController::class, 'questions'])
            ->middleware('throttle:1,5')
            ->name('questions');
        Route::post('/sync-answers', [OfflineController::class, 'syncAnswers'])
            ->name('sync-answers');
    });
});

/*
|--------------------------------------------------------------------------
| 2FA — challenge e setup (autenticati, senza middleware 2fa per evitare loop)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->prefix('2fa')->name('2fa.')->group(function () {
    Route::get('/challenge', [TwoFactorChallengeController::class, 'show'])->name('challenge.show');
    Route::post('/challenge', [TwoFactorChallengeController::class, 'verify'])->name('challenge.verify');

    Route::get('/setup', [TwoFactorSetupController::class, 'show'])->name('setup.show');
    Route::post('/setup', [TwoFactorSetupController::class, 'store'])->name('setup.store');

    Route::get('/codes', [TwoFactorSetupController::class, 'showCodes'])->name('codes.show');
    Route::post('/codes/confirm', [TwoFactorSetupController::class, 'confirmCodes'])->name('codes.confirm');

    Route::post('/disable', [TwoFactorSetupController::class, 'disable'])->name('disable');
    Route::post('/codes/regenerate', [TwoFactorSetupController::class, 'regenerateCodes'])->name('codes.regenerate');
});

/*
|--------------------------------------------------------------------------
| ADMIN AREA
|--------------------------------------------------------------------------
| Tutti i ruoli (admin/editor/viewer) possono accedere al pannello.
| Le singole azioni sono protette nei controller tramite hasPermission().
*/
Route::middleware(['auth', '2fa'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        /*
        | INDICI + AZIONI (controller-level permission check)
        */
        Route::middleware('role:admin,editor,viewer')->group(function () {

            // CATEGORIES
            Route::resource('categories', CategoryController::class)
                ->except(['show']);

            // CATEGORY MATERIALS
            Route::post('categories/{category}/materials/reorder', [CategoryMaterialController::class, 'reorder'])
                ->name('categories.materials.reorder');
            Route::resource('categories.materials', CategoryMaterialController::class)
                ->except(['show']);

            // QUESTIONS
            Route::get('questions/data', [QuestionController::class, 'data'])
                ->name('questions.data');
            Route::get('questions/export', [QuestionController::class, 'export'])
                ->name('questions.export');
            Route::post('questions/import', [QuestionController::class, 'import'])
                ->name('questions.import');
            Route::get('questions/template', [QuestionController::class, 'template'])
                ->name('questions.template');
            Route::post('questions/bulk-delete', [QuestionController::class, 'bulkDelete'])
                ->name('questions.bulkDelete');
            Route::get('questions/mit-import', [QuestionController::class, 'showMitImport'])
                ->name('questions.mit-import');
            Route::post('questions/mit-import', [QuestionController::class, 'storeMitImport'])
                ->name('questions.mit-import.store');
            Route::resource('questions', QuestionController::class)
                ->except(['show']);

            // QUIZZES
            Route::post('quizzes/random', [QuizController::class, 'createRandom'])
                ->name('quizzes.random');
            Route::get('quizzes/{quiz}/questions/data', [QuizController::class, 'questionsData'])
                ->name('quizzes.questions.data');
            Route::get('quizzes/{quiz}/questions', [QuizController::class, 'manageQuestions'])
                ->name('quizzes.questions');
            Route::get('quizzes/{quiz}/questions-list', [QuizController::class, 'questionsList'])
                ->name('quizzes.questions.list');
            Route::post('quizzes/{quiz}/fill-random', [QuizController::class, 'fillRandom'])
                ->name('quizzes.fillRandom');
            Route::post('quizzes/{quiz}/update-params', [QuizController::class, 'updateParams'])
                ->name('quizzes.updateParams');
            Route::post('quizzes/{quiz}/reorder', [QuizController::class, 'reorder'])
                ->name('quizzes.reorder');
            Route::post('quizzes/{quiz}/questions/add', [QuizController::class, 'addQuestion'])
                ->name('quizzes.questions.add');
            Route::post('quizzes/{quiz}/questions/remove', [QuizController::class, 'removeQuestion'])
                ->name('quizzes.questions.remove');
            Route::post('quizzes/{quiz}/bulk-add', [QuizController::class, 'bulkAdd'])
                ->name('quizzes.bulkAdd');
            Route::post('quizzes/{quiz}/bulk-remove', [QuizController::class, 'bulkRemove'])
                ->name('quizzes.bulkRemove');
            Route::resource('quizzes', QuizController::class)
                ->except(['show', 'edit', 'update']);

            // USERS
            Route::get('users/{user}/stats', [UserStatsController::class, 'show'])
                ->name('users.stats');
            Route::resource('users', AdminUserController::class)
                ->except(['show']);

            // QUESTION REPORTS (segnalazioni domande) — autorizzazione per-azione
            // tramite canEditQuestion() nel controller (admin/editor passa, viewer 403).
            Route::prefix('question-reports')->name('question-reports.')->group(function () {
                Route::get('/',                   [QuestionReportController::class, 'index'])->name('index');
                Route::get('/{report}',           [QuestionReportController::class, 'show'])->name('show');
                Route::patch('/{report}/accept',  [QuestionReportController::class, 'accept'])->name('accept');
                Route::patch('/{report}/reject',  [QuestionReportController::class, 'reject'])->name('reject');
                Route::delete('/{report}',        [QuestionReportController::class, 'destroy'])->name('destroy');
            });
        });

        /*
        | SOLO ADMIN — gestione sistema (dashboard, audit, role-permissions, media)
        */
        Route::middleware('role:admin')->group(function () {

            // MEDIA MANAGER
            Route::get('media', fn () => view('admin.media.index'))->name('media.index');

            Route::get('stats', [DashboardController::class, 'index'])
                ->name('stats');

            // REPORT PERIODICI
            Route::prefix('reports')->name('reports.')->group(function () {
                Route::get('/',           [ReportController::class, 'index'])->name('index');
                Route::get('/show',       [ReportController::class, 'show'])->name('show');
                Route::get('/export-pdf', [ReportController::class, 'exportPdf'])->name('export-pdf');
            });

            Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit.index');
            Route::get('audit-logs/export', [AuditLogController::class, 'export'])->name('audit.export');
            Route::get('audit-logs/{log}', [AuditLogController::class, 'show'])->name('audit.show');

            Route::get('quiz-attempts', [QuizAttemptController::class, 'adminIndex'])
                ->name('quiz.attempts.all');

            // QUIZ STATE TRANSITIONS (admin only)
            Route::post('quizzes/{quiz}/publish', [QuizController::class, 'publish'])
                ->name('quizzes.publish');
            Route::post('quizzes/{quiz}/unpublish', [QuizController::class, 'unpublish'])
                ->name('quizzes.unpublish');
            Route::post('quizzes/{quiz}/confirm', [QuizController::class, 'confirm'])
                ->name('quizzes.confirm');
            Route::get('confirmed-results', [QuizController::class, 'confirmedResults'])
                ->name('quizzes.confirmedResults');

            // RIEPILOGO + EXPORT EXCEL (solo per quiz confermati)
            Route::get('quizzes/{quiz}/summary', [QuizController::class, 'summary'])
                ->name('quizzes.summary');
            Route::get('quizzes/{quiz}/export-results', [QuizController::class, 'exportResults'])
                ->name('quizzes.export-results');

            // SCHEDULAZIONE ISCRIZIONI (solo per quiz confermati)
            Route::get('quizzes/{quiz}/schedule', [QuizController::class, 'editSchedule'])
                ->name('quizzes.schedule.edit');
            Route::put('quizzes/{quiz}/schedule', [QuizController::class, 'updateSchedule'])
                ->name('quizzes.schedule.update');

            // QUIZ ENROLLMENTS (admin only)
            Route::get('enrollments', [QuizEnrollmentController::class, 'adminIndex'])
                ->name('enrollments.index');
            Route::post('enrollments/{enrollment}/approve', [QuizEnrollmentController::class, 'approve'])
                ->name('enrollments.approve');
            Route::post('enrollments/{enrollment}/reject', [QuizEnrollmentController::class, 'reject'])
                ->name('enrollments.reject');
            Route::post('quizzes/{quiz}/enrollments/reopen/{user}', [QuizEnrollmentController::class, 'reopen'])
                ->name('enrollments.reopen');

            // Ruoli & Permessi
            Route::get('roles', [RolePermissionController::class, 'index'])
                ->name('roles.index');
            Route::put('roles', [RolePermissionController::class, 'update'])
                ->name('roles.update');

            // COMANDI UTILI (artisan via UI)
            Route::get('commands', [AdminCommandController::class, 'index'])
                ->name('commands.index');
            Route::post('commands/{slug}', [AdminCommandController::class, 'run'])
                ->where('slug', '[a-z0-9\-]+')
                ->name('commands.run');

            // ISCRIZIONI ANAGRAFICHE (richieste viewer)
            Route::get('registrations', [AdminRegistrationController::class, 'index'])
                ->name('registrations.index');
            Route::get('registrations/{user}', [AdminRegistrationController::class, 'show'])
                ->name('registrations.show');
            Route::post('registrations/{user}/approve', [AdminRegistrationController::class, 'approve'])
                ->name('registrations.approve');
            Route::post('registrations/{user}/reject', [AdminRegistrationController::class, 'reject'])
                ->name('registrations.reject');

            // HEALTH DASHBOARD (stato sistema, backup, code, disco)
            Route::get('health', [HealthController::class, 'index'])
                ->name('health.index');
            Route::post('health/backup-now', [HealthController::class, 'runBackupNow'])
                ->name('health.backup-now');

            // GESTIONE ISTRUTTORI (assegnazione studenti)
            Route::get('instructors', [InstructorAssignmentController::class, 'index'])
                ->name('instructors.index');
            Route::get('instructors/{instructor}/assignments', [InstructorAssignmentController::class, 'edit'])
                ->name('instructors.edit');
            Route::post('instructors/{instructor}/assign', [InstructorAssignmentController::class, 'assign'])
                ->name('instructors.assign');
            Route::delete('instructors/{instructor}/students/{student}', [InstructorAssignmentController::class, 'unassign'])
                ->name('instructors.unassign');
        });
    });

/*
|--------------------------------------------------------------------------
| INSTRUCTOR AREA — sola lettura progressi studenti assegnati
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', '2fa', 'role:admin,instructor'])
    ->prefix('instructor')
    ->name('instructor.')
    ->group(function () {
        Route::get('students', [InstructorController::class, 'index'])
            ->name('students.index');
        Route::get('students/{student}', [InstructorController::class, 'showStudent'])
            ->name('students.show');
    });

/*
|--------------------------------------------------------------------------
| EDITOR AREA
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', '2fa', 'role:admin,editor'])
    ->group(function () {
        Route::get('editor/dashboard', [EditorDashboardController::class, 'index'])
            ->name('editor.dashboard');
    });

require __DIR__.'/auth.php';
