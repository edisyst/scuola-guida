<?php

namespace App\Http\Controllers;

use App\Models\QuizAttempt;
use App\Services\BadgeService;
use App\Services\SimulatorService;
use App\Services\StreakService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SimulatorController extends Controller
{
    public function __construct(
        private SimulatorService $service,
        private StreakService $streakService,
        private BadgeService $badgeService,
    ) {}

    public function index(): View
    {
        return view('simulator.index', [
            'questions' => (int) config('simulator.questions'),
            'timeLimit' => (int) config('simulator.time_limit'),
            'maxErrors' => (int) config('simulator.max_errors'),
        ]);
    }

    public function start(): RedirectResponse
    {
        // Pulisce una sessione eventualmente abbandonata prima di avviarne una nuova.
        $this->service->clearSession();

        $questions = $this->service->buildQuestionList();

        if ($questions->isEmpty()) {
            return redirect()->route('simulator.index')
                ->with('error', 'Non ci sono domande disponibili per avviare il simulatore.');
        }

        $this->service->startSession(auth()->id(), $questions);

        return redirect()->route('simulator.play');
    }

    public function play(): View|RedirectResponse
    {
        if (!$this->service->hasActiveSession()) {
            return redirect()->route('simulator.index')
                ->with('warning', 'Sessione scaduta. Avvia una nuova simulazione.');
        }

        $attempt   = QuizAttempt::findOrFail($this->service->currentAttemptId());
        $questions = $this->service->loadSessionQuestions();

        // Stesso payload JSON usato dalla view quiz.play (id/text/image/correct).
        $questionsJson = $questions->map(fn ($q) => [
            'id'      => $q->id,
            'text'    => $q->question,
            'image'   => $q->image ? asset('storage/' . $q->image) : null,
            'correct' => (int) $q->is_true,
        ])->values()->all();

        return view('simulator.play', [
            'attempt'       => $attempt,
            'questionsJson' => $questionsJson,
            'timeLimit'     => (int) config('simulator.time_limit') * 60,
            'maxErrors'     => (int) config('simulator.max_errors'),
        ]);
    }

    /**
     * Endpoint autosave del simulatore. Replica il contratto di
     * PUT /quiz/attempts/{attempt} ma usa SimulatorService perché
     * QuizAttemptService::updateAttempt dipende da $attempt->quiz->questions
     * (e per il simulatore quiz_id è null).
     */
    public function autosave(Request $request, QuizAttempt $attempt): JsonResponse
    {
        $this->authorizeAttempt($attempt);

        $data = $request->validate([
            'answers'           => 'array',
            'answers.*.correct' => 'integer|in:0,1',
            'duration'          => 'nullable|integer|min:0',
        ]);

        $attempt = $this->service->updateAttempt(
            $attempt,
            $data['answers'] ?? [],
            $data['duration'] ?? null,
        );

        return response()->json([
            'success' => true,
            'score'   => $attempt->score,
        ]);
    }

    public function submit(Request $request): RedirectResponse
    {
        $attemptId = $this->service->currentAttemptId();

        if (!$attemptId) {
            return redirect()->route('simulator.index');
        }

        $attempt = QuizAttempt::findOrFail($attemptId);
        $this->authorizeAttempt($attempt);

        $data = $request->validate([
            'answers'  => 'array',
            'duration' => 'nullable|integer|min:0',
        ]);

        $attempt = $this->service->updateAttempt(
            $attempt,
            $data['answers'] ?? [],
            $data['duration'] ?? 0,
        );

        $this->service->clearSession();

        $user      = auth()->user();
        $maxErrors = (int) config('simulator.max_errors', 4);
        $passed    = ($attempt->total_questions - $attempt->score) <= $maxErrors;

        $this->streakService->recordActivity($user);

        if ($passed) {
            $this->badgeService->awardIfEligible($user, 'first_pass', [
                'score'           => $attempt->score,
                'total_questions' => $attempt->total_questions,
                'date'            => now()->toDateString(),
            ]);
        }

        $this->badgeService->checkAllBadges($user);

        return redirect()->route('simulator.result', $attempt)
            ->with('success', 'Simulazione completata. Ecco il tuo risultato.');
    }

    public function result(QuizAttempt $attempt): View
    {
        $this->authorizeAttempt($attempt);

        $detail = $this->service->getResultDetail($attempt);

        return view('simulator.result', $detail);
    }

    public function destroy(): RedirectResponse
    {
        $this->service->clearSession();

        return redirect()->route('simulator.index')
            ->with('info', 'Simulazione annullata.');
    }

    private function authorizeAttempt(QuizAttempt $attempt): void
    {
        if ($attempt->user_id !== auth()->id()) {
            abort(403);
        }
    }
}
