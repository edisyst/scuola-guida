<?php

namespace App\Http\Controllers;

use App\Http\Requests\StartStudyRequest;
use App\Models\Category;
use App\Models\Question;
use App\Models\Quiz;
use App\Services\BadgeService;
use App\Services\SpacedRepetitionService;
use App\Services\StreakService;
use App\Services\StudyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class StudyController extends Controller
{
    public function __construct(private StudyService $service) {}

    public function index(): View
    {
        $quizzes = Quiz::query()
            ->whereIn('status', [Quiz::STATUS_PUBLISHED, Quiz::STATUS_CONFIRMED])
            ->withCount('questions')
            ->orderBy('title')
            ->get();

        $categories = Category::query()
            ->withCount('questions')
            ->orderBy('name')
            ->get();

        return view('study.index', [
            'quizzes'    => $quizzes,
            'categories' => $categories,
            'hasSession' => $this->service->hasSession(),
        ]);
    }

    public function start(StartStudyRequest $request): RedirectResponse
    {
        try {
            $this->service->start($request->input('source'), $request->sourceId());
        } catch (RuntimeException $e) {
            if ($request->input('source') === StudyService::SOURCE_BOOKMARKS) {
                return redirect()->route('bookmarks.index')
                    ->with('warning', 'Non hai domande salvate da studiare.');
            }
            return back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()->route('study.play');
    }

    public function play(Request $request): RedirectResponse|View
    {
        if (!$this->service->hasSession()) {
            return redirect()->route('study.index')
                ->with('info', 'Avvia una nuova sessione di studio per iniziare.');
        }

        if ($request->has('index')) {
            $this->service->setIndex((int) $request->query('index'));
        }

        $question = $this->service->currentQuestion();

        if (!$question) {
            return redirect()->route('study.index')
                ->with('error', 'La sessione di studio non contiene domande valide.');
        }

        $question->loadMissing('category');
        if ($question->category) {
            $question->category->load(['materials' => fn($q) => $q->ordered()]);
        }

        return view('study.play', [
            'question'   => $question,
            'index'      => $this->service->currentIndex(),
            'total'      => $this->service->count(),
            'isFlagged'  => $this->service->isFlagged($question->id),
        ]);
    }

    public function flag(Request $request, int $question): JsonResponse
    {
        $data = $request->validate([
            'answer' => 'nullable|in:0,1',
            'toggle' => 'nullable|boolean',
        ]);

        try {
            $flagged = null;

            if (($data['toggle'] ?? false)) {
                $flagged = $this->service->toggleFlag($question);
            }

            if (array_key_exists('answer', $data) && $data['answer'] !== null) {
                $this->service->recordAnswer($question, (int) $data['answer']);

                $q = Question::find($question);
                if ($q && auth()->check()) {
                    $user      = auth()->user();
                    $isCorrect = (int) $data['answer'] === (int) $q->is_true;
                    app(SpacedRepetitionService::class)->recordAnswer($user, $question, $isCorrect);
                    app(StreakService::class)->recordActivity($user);
                    app(BadgeService::class)->checkAllBadges($user);
                }
            }
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json([
            'flagged'       => $flagged,
            'flagged_count' => count($this->service->flaggedIds()),
        ]);
    }

    public function summary(): RedirectResponse|View
    {
        if (!$this->service->hasSession()) {
            return redirect()->route('study.index')
                ->with('info', 'Nessuna sessione di studio attiva.');
        }

        return view('study.summary', [
            'summary' => $this->service->summary(),
        ]);
    }

    public function destroy(): RedirectResponse
    {
        $this->service->clear();

        return redirect()->route('study.index')
            ->with('success', 'Sessione di studio terminata.');
    }
}
