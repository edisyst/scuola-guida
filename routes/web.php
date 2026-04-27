<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\QuizController;

Route::get('/', function () {
    return view('welcome');
});

// testare su /login con user=admin@test.com password=password poi andare su /admin
Route::get('/admin', function () {
    return view('adminlte::page');
})->middleware(['auth', 'admin']);

// categories
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::resource('categories', CategoryController::class);
});
// questions
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::resource('questions', QuestionController::class);
    Route::get('/admin/questions/data', [QuestionController::class, 'data'])
        ->name('questions.data');
    Route::get('/admin/questions/export', [QuestionController::class, 'export'])
        ->name('questions.export');
    Route::post('/admin/questions/import', [QuestionController::class, 'import'])
        ->name('questions.import');
    Route::get('/admin/questions/template', [QuestionController::class, 'template'])
        ->name('questions.template');
    Route::post('/admin/questions/bulk-delete', [QuestionController::class, 'bulkDelete'])
        ->name('questions.bulkDelete');
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
