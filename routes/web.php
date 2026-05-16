<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\QuizAttemptController;
use App\Http\Controllers\QuizEnrollmentController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\UserStatsController;
use App\Http\Controllers\Admin\RolePermissionController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\RegistrationController as AdminRegistrationController;
use App\Models\AuditLog;

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
});

/*
|--------------------------------------------------------------------------
| ADMIN AREA
|--------------------------------------------------------------------------
| Tutti i ruoli (admin/editor/viewer) possono accedere al pannello.
| Le singole azioni sono protette nei controller tramite hasPermission().
*/
Route::middleware(['auth'])
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
        });

        /*
        | SOLO ADMIN — gestione sistema (dashboard, audit, role-permissions, media)
        */
        Route::middleware('role:admin')->group(function () {

            // MEDIA MANAGER
            Route::get('media', fn () => view('admin.media.index'))->name('media.index');

            Route::get('stats', [DashboardController::class, 'index'])
                ->name('stats');

            Route::get('audit-logs', function () {
                $logs = AuditLog::with('user')->latest()->paginate(20);
                return view('admin.audit.index', compact('logs'));
            })->name('audit.index');

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

            // ISCRIZIONI ANAGRAFICHE (richieste viewer)
            Route::get('registrations', [AdminRegistrationController::class, 'index'])
                ->name('registrations.index');
            Route::get('registrations/{user}', [AdminRegistrationController::class, 'show'])
                ->name('registrations.show');
            Route::post('registrations/{user}/approve', [AdminRegistrationController::class, 'approve'])
                ->name('registrations.approve');
            Route::post('registrations/{user}/reject', [AdminRegistrationController::class, 'reject'])
                ->name('registrations.reject');
        });
    });

require __DIR__.'/auth.php';
