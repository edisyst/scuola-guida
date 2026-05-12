<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\QuizAttemptController;

Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| AUTH AREA — tutti gli utenti autenticati
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Quiz: gioca (viewer / user)
    Route::get('quiz/random-play', [QuizController::class, 'randomPlay'])->name('quiz.random');
    Route::get('quiz/{quiz}/play', [QuizController::class, 'play'])->name('quiz.play');
    Route::post('quiz/submit', [QuizController::class, 'submit'])->name('quiz.submit');
    Route::get('quiz/results', [QuizController::class, 'results'])->name('quiz.results');

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
*/
Route::middleware(['auth'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        /*
        | ADMIN + EDITOR — gestione categorie, domande, quiz
        */
        Route::middleware('role:admin,editor')->group(function () {

            Route::resource('categories', CategoryController::class)
                ->except(['show']);

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
        });

        /*
        | SOLO ADMIN — utenti, dashboard, audit, tutti i risultati
        */
        Route::middleware('role:admin')->group(function () {

            Route::resource('users', \App\Http\Controllers\Admin\UserController::class)
                ->except(['show']);

            Route::get('dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])
                ->name('dashboard');

            Route::get('audit-logs', function () {
                $logs = \App\Models\AuditLog::with('user')->latest()->paginate(20);
                return view('admin.audit.index', compact('logs'));
            })->name('audit.index');

            // Tutti i tentativi di tutti gli utenti
            Route::get('quiz-attempts', [QuizAttemptController::class, 'adminIndex'])
                ->name('quiz.attempts.all');
        });
    });

// Dashboard Breeze (legacy)
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/auth.php';
