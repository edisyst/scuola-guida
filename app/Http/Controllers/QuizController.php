<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Quiz;
use App\Models\QuizResult;
use App\Models\Question;
use App\Services\QuizService;

class QuizController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | CRUD
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $quizzes = Quiz::latest()->get();

        return view('admin.quizzes.index', compact('quizzes'));
    }

    public function create()
    {
        return view('admin.quizzes.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'     => 'nullable', //|string forse??? o forse si può togliere?
        ]);

        $data['is_active'] = $request->has('is_active');

        $quiz = Quiz::create($data);
        clearAdminBadgesCache();

        return redirect()->route('admin.quizzes.index')
            ->with('success', 'Quiz creato');
    }

    public function edit(Quiz $quiz)
    {
        return view('admin.quizzes.edit', compact('quiz'));
    }

    public function update(Request $request, Quiz $quiz)
    {
        $data = $request->validate([
            'title' => 'nullable|string', // forse si può togliere?
        ]);

        $data['is_active'] = $request->has('is_active');

        $quiz->update($data);
        clearAdminBadgesCache();

        return redirect()->route('admin.quizzes.index')
            ->with('success', 'Quiz aggiornato');
    }

    public function destroy(Quiz $quiz)
    {
        $quiz->delete();
        clearAdminBadgesCache();

        return back()->with('success', 'Quiz eliminato');
    }

    /*
    |--------------------------------------------------------------------------
    | ALTRI METODI
    |--------------------------------------------------------------------------
    */

    public function randomPlay()
    {
        $ids = Question::inRandomOrder()->limit(10)->pluck('id');
        $questions = Question::whereIn('id', $ids)->get();

        return view('quiz.play', compact('questions'));
    }

    public function play(Quiz $quiz)
    {
        // per ora stesso comportamento (poi colleghiamo domande al quiz)
        $questions = Question::inRandomOrder()->limit(10)->get();

        return view('quiz.play', compact('questions', 'quiz'));
    }

    public function submit(Request $request, QuizService $service)
    {
        $answers = $request->input('answers', []);

        $score = $service->calculateScore($answers);

        QuizResult::create([
            'user_id' => auth()->id(),
            'score' => $score,
            'total' => count($answers),
        ]);

        return response()->json([
            'score' => $score
        ]);
    }

    public function results()
    {
        $results = QuizResult::with('user')->latest()->get();

        return view('quiz.results', compact('results'));
    }
}
