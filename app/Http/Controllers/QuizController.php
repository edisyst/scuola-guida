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
        $quizzes = Quiz::withCount('questions')->latest()->get();

        return view('admin.quizzes.index', compact('quizzes'));
    }

    public function create()
    {
        $questions = Question::limit(200)->get(); // evita carichi enormi

        return view('admin.quizzes.create', compact('questions'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'         => 'nullable', //|string forse??? o forse si può togliere?
            'questions'     => 'nullable|array',
            'questions.*'   => 'exists:questions,id',
        ]);

        $data['is_active'] = $request->has('is_active');

        $quiz = Quiz::create($data);

        // 🔥 collega domande
        if (!empty($data['questions'])) {
            $quiz->questions()->sync($data['questions']);
        }

        clearAdminBadgesCache();

        return redirect()->route('admin.quizzes.index')
            ->with('success', 'Quiz creato');
    }

    public function edit(Quiz $quiz)
    {
        $questions = Question::limit(200)->get();
//        $quiz->load('questions');

        return view('admin.quizzes.edit', compact('quiz', 'questions'));
    }

    public function update(Request $request, Quiz $quiz)
    {
        $data = $request->validate([
            'title' => 'nullable|string', // forse si può togliere?
            'questions' => 'nullable|array',
            'questions.*' => 'exists:questions,id',
        ]);

        $data['is_active'] = $request->has('is_active');

        $quiz->update($data);

        // 🔥 aggiorna pivot
        $quiz->questions()->sync($data['questions'] ?? []);

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
