<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\QuizAttemptController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\UserStatsController;
use App\Http\Controllers\Admin\RolePermissionController;

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

    // Quiz: gioca (viewer / user)
    Route::get('quiz/random-play', [QuizController::class, 'randomPlay'])->name('quiz.random');
    Route::get('quiz/{quiz}/play', [QuizController::class, 'play'])->name('quiz.play');
    Route::post('quiz/submit', [QuizController::class, 'submit'])->name('quiz.submit');
    Route::get('quiz/results', [QuizController::class, 'results'])->name('quiz.results');

    // Dashboard personale stats utente
    Route::get('stats', [UserStatsController::class, 'me'])->name('stats.me');
    Route::post('stats/{user}/refresh', [UserStatsController::class, 'refresh'])
        ->name('stats.refresh');

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
            Route::post('quizzes/{quiz}/questions-list', [QuizController::class, 'questionsList'])
                ->name('quizzes.questions.list');
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
                ->except(['show']);

            // USERS
            Route::get('users/{user}/stats', [UserStatsController::class, 'show'])
                ->name('users.stats');
            Route::resource('users', \App\Http\Controllers\Admin\UserController::class)
                ->except(['show']);
        });

        /*
        | SOLO ADMIN — gestione sistema (dashboard, audit, role-permissions)
        */
        Route::middleware('role:admin')->group(function () {

            Route::get('dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])
                ->name('dashboard');

            Route::get('audit-logs', function () {
                $logs = \App\Models\AuditLog::with('user')->latest()->paginate(20);
                return view('admin.audit.index', compact('logs'));
            })->name('audit.index');

            Route::get('quiz-attempts', [QuizAttemptController::class, 'adminIndex'])
                ->name('quiz.attempts.all');

            // Ruoli & Permessi
            Route::get('roles', [RolePermissionController::class, 'index'])
                ->name('roles.index');
            Route::put('roles', [RolePermissionController::class, 'update'])
                ->name('roles.update');
        });
    });

// Dashboard Breeze (legacy)
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/auth.php';
