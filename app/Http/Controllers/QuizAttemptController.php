<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuizAttemptRequest;
use App\Http\Requests\UpdateQuizAttemptRequest;
use App\Models\QuizAttempt;
use App\Services\QuizAttemptService;

class QuizAttemptController extends Controller
{
    public function __construct(private QuizAttemptService $service) {}

    public function index()
    {
        $attempts = QuizAttempt::with('quiz')
            ->where('user_id', auth()->id())
            ->latest()
            ->paginate(15);

        return view('quiz.attempts', compact('attempts'));
    }

    public function store(StoreQuizAttemptRequest $request)
    {
        $data = $request->validated();

        $attempt = $this->service->record(
            auth()->id(),
            $data['quiz_id'],
            $data['answers'],
            $data['duration'] ?? null,
        );

        return response()->json([
            'success'    => true,
            'attempt_id' => $attempt->id,
            'score'      => $attempt->score,
            'total'      => $attempt->total_questions,
            'percentage' => $attempt->percentage,
            'passed'     => $attempt->is_passed,
        ]);
    }

    public function adminIndex()
    {
        $attempts = QuizAttempt::with(['quiz', 'user'])
            ->latest()
            ->paginate(20);

        return view('admin.quiz-attempts.index', compact('attempts'));
    }

    public function show(QuizAttempt $attempt)
    {
        // IDOR guard: un viewer può vedere solo i propri tentativi.
        // Admin e utenti con canEditUser() possono vedere qualsiasi tentativo.
        $user = auth()->user();
        if ($attempt->user_id !== $user->id && !$user->isAdmin() && !$user->canEditUser()) {
            abort(403);
        }

        // Eager load del quiz: la view accede a $attempt->quiz->isConfirmed() per
        // decidere il link "Riprova" (W-4). Senza loadMissing si scatena una lazy query.
        $attempt->loadMissing('quiz');

        return view('quiz.attempt', compact('attempt'));
    }

    public function update(UpdateQuizAttemptRequest $request, QuizAttempt $attempt)
    {
        $data = $request->validated();

        $attempt = $this->service->updateAttempt(
            $attempt,
            $data['answers'],
            $data['duration'] ?? null,
        );

        return response()->json([
            'success' => true,
            'score'   => $attempt->score,
        ]);
    }
}
