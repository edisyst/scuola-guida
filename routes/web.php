<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\QuizController;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        // accesso base admin area
        Route::middleware('role:admin,editor,viewer')->group(function () {

            // categories
            Route::resource('categories', CategoryController::class)
                ->except(['show']);

            // questions
            Route::get('questions/data', [QuestionController::class, 'data'])
                ->name('questions.data');

            Route::resource('questions', QuestionController::class)
                ->except(['show']);

            Route::get('questions/export', [QuestionController::class, 'export'])
                ->name('questions.export');

            Route::post('questions/import', [QuestionController::class, 'import'])
                ->name('questions.import');

            Route::get('questions/template', [QuestionController::class, 'template'])
                ->name('questions.template');

            Route::post('questions/bulk-delete', [QuestionController::class, 'bulkDelete'])
                ->name('questions.bulkDelete');
        });

        // SOLO ADMIN: audit-logs
        Route::middleware('role:admin')->group(function () {
            Route::get('audit-logs', function () {
                $logs = \App\Models\AuditLog::with('user')
                    ->latest()
                    ->paginate(20);
                return view('admin.audit.index', compact('logs'));
            })->name('audit.index');
        });

        // SOLO ADMIN: users
        Route::middleware('role:admin')->group(function () {
            Route::resource('users', \App\Http\Controllers\Admin\UserController::class)
                ->except(['show']);

        });
    });

// quiz
Route::middleware(['auth'])->group(function () {
    Route::get('/quiz/play', [QuizController::class, 'play'])->name('quiz.play');
    Route::post('/quiz/submit', [QuizController::class, 'submit'])->name('quiz.submit');
    Route::get('/quiz/results', [QuizController::class, 'results'])->name('quiz.results');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
