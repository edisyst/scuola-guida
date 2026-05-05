<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuizRequest;
use App\Http\Requests\UpdateQuizRequest;
use App\Models\QuizAttempt;
use App\Models\Quiz;
use App\Models\QuizResult;
use App\Services\QuizService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class QuizAttemptController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'quiz_id' => 'required|exists:quizzes,id',
            'answers' => 'required|array',
            'answers.*' => 'in:0,1',
            'duration' => 'nullable|integer|min:0',
        ]);

        $quiz = Quiz::with('questions:id,is_true')->findOrFail($data['quiz_id']);

        $correctMap = $quiz->questions->pluck('is_true', 'id');

        $score = 0;

        foreach ($data['answers'] as $questionId => $answer) {

            if (!isset($correctMap[$questionId])) {
                continue; // sicurezza
            }

            if ((int)$answer === (int)$correctMap[$questionId]) {
                $score++;
            }
        }

        $attempt = QuizAttempt::create([
            'user_id' => auth()->id(), // 🔥 se guest, gestisci dopo
            'quiz_id' => $quiz->id,
            'score' => $score,
            'total_questions' => count($correctMap),
            'duration' => $data['duration'] ?? null,
            'answers' => $data['answers'],
        ]);

        return response()->json([
            'success' => true,
            'attempt_id' => $attempt->id,
            'score' => $score,
            'total' => count($correctMap),
            'percentage' => $attempt->percentage,
            'passed' => $attempt->is_passed,
        ]);
    }

    public function show(QuizAttempt $attempt)
    {
        return view('quiz.attempt', compact('attempt'));
    }
}
