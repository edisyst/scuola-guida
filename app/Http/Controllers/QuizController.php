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
    public function play()
    {
        $ids = Question::inRandomOrder()->limit(10)->pluck('id');
        $questions = Question::whereIn('id', $ids)->get();

        return view('quiz.play', compact('questions'));
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
