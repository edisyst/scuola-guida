<?php

namespace App\Http\Controllers;

use App\DataTables\QuizQuestionsDataTable;
use App\Exports\QuizResultsExport;
use App\Http\Requests\BulkQuizQuestionsRequest;
use App\Http\Requests\StoreQuizRequest;
use App\Http\Requests\UpdateQuizScheduleRequest;
use App\Models\Question;
use App\Models\Quiz;
use App\Services\QuizEnrollmentService;
use App\Services\QuizService;
use App\Services\QuizSummaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;

class QuizController extends Controller
{
    public function __construct(
        private QuizService $service,
        private QuizEnrollmentService $enrollmentService,
    ) {}

    /*
    |--------------------------------------------------------------------------
    | CRUD
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $quizzes = Quiz::withCount('questions')->get();

        return view('admin.quizzes.index', compact('quizzes'));
    }

    public function create()
    {
        abort_unless(auth()->user()->canCreateQuiz(), 403);

        $questions = Question::limit(200)->get();

        return view('admin.quizzes.create', compact('questions'));
    }

    public function store(StoreQuizRequest $request)
    {
        $this->service->create($request->validated());

        return redirect()->route('admin.quizzes.index')
            ->with('success', 'Quiz creato');
    }

    public function destroy(Quiz $quiz)
    {
        abort_unless(auth()->user()->canDeleteQuiz(), 403);

        if ($quiz->isLocked()) {
            return back()->with('error', 'Quiz confermato: non è possibile eliminarlo.');
        }

        $quiz->delete();

        return back()->with('success', 'Quiz eliminato');
    }

    /*
    |--------------------------------------------------------------------------
    | STATE TRANSITIONS (admin only)
    |--------------------------------------------------------------------------
    */

    public function publish(Quiz $quiz)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        try {
            $this->service->publish($quiz);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Quiz pubblicato.');
    }

    public function unpublish(Quiz $quiz)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        try {
            $this->service->unpublish($quiz);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Quiz riportato in bozza.');
    }

    public function confirm(Quiz $quiz)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        try {
            $this->service->confirm($quiz, auth()->id());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Quiz confermato. Non sarà più modificabile.');
    }

    /*
    |--------------------------------------------------------------------------
    | ALTRI METODI
    |--------------------------------------------------------------------------
    */

    public function questionsList(Quiz $quiz)
    {
        return $quiz->questions()
            ->orderBy('pivot_order')
            ->get(['id', 'question']);
    }

    public function reorder(Request $request, Quiz $quiz)
    {
        abort_unless(auth()->user()->canEditQuiz(), 403);

        try {
            $this->service->reorderQuestions($quiz, $request->input('ids', []));
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true]);
    }

    public function questionsData(Request $request, Quiz $quiz, QuizQuestionsDataTable $dataTable)
    {
        return $dataTable->response($request, $quiz);
    }

    public function manageQuestions(Quiz $quiz)
    {
        $quiz->load('questions');

        // $questions rimossa: la tabella carica via AJAX (admin.quizzes.questions.data).
        // $categories serve solo per il filtro dropdown nella view.
        $categories   = \App\Models\Category::orderBy('name')->get(['id', 'name']);
        $currentCount = $quiz->questions()->count();
        $max          = $quiz->max_questions;

        return view('admin.quizzes.questions', compact('quiz', 'categories', 'currentCount', 'max'));
    }

    public function addQuestion(Request $request, Quiz $quiz)
    {
        abort_unless(auth()->user()->canEditQuiz(), 403);

        try {
            $result = $this->service->addQuestion($quiz, (int) $request->question_id);
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        if (!$result['ok']) {
            return response()->json(['error' => $result['error']], 422);
        }

        return response()->json(['current' => $result['current']]);
    }

    public function removeQuestion(Request $request, Quiz $quiz)
    {
        abort_unless(auth()->user()->canEditQuiz(), 403);

        try {
            $current = $this->service->removeQuestion($quiz, (int) $request->question_id);
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json(['current' => $current]);
    }

    public function createRandom()
    {
        abort_unless(auth()->user()->canCreateQuiz(), 403);

        $quiz = $this->service->createRandom();

        return redirect()
            ->route('admin.quizzes.index')
            ->with('success', 'Quiz creato con ' . $quiz->questions()->count() . ' domande');
    }

    public function play(Quiz $quiz)
    {
        $user = auth()->user();

        $enrollment   = null;
        $enrollmentId = null;

        if ($quiz->isConfirmed()) {
            $enrollment = $this->enrollmentService->activeFor($quiz, $user);

            if (!$enrollment || !$enrollment->isApproved()) {
                abort(403, 'Per svolgere questo quiz serve un\'iscrizione approvata.');
            }

            $enrollmentId = $enrollment->id;
        } elseif ($quiz->isDraft()) {
            abort_unless($user->canEditQuiz() || $user->isAdmin(), 403);
        }

        $session = $this->service->startPlay($quiz, $user->id, $enrollmentId);

        // Consuma subito l'iscrizione: il viewer può svolgere il quiz una sola volta.
        if ($enrollment) {
            $this->enrollmentService->markCompleted($enrollment, $session['attempt']);
        }

        return view('quiz.play', [
            'quiz'          => $quiz,
            'timeLimit'     => $quiz->time_limit,
            'maxErrors'     => $quiz->max_errors,
            'attemptId'     => $session['attempt']->id,
            'questionsJson' => $session['questions_json'],
        ]);
    }

    public function confirmedResults()
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $attempts = \App\Models\QuizAttempt::with(['quiz', 'user', 'enrollment'])
            ->whereHas('quiz', fn ($q) => $q->where('status', Quiz::STATUS_CONFIRMED))
            ->whereNotNull('quiz_enrollment_id')
            ->latest()
            ->paginate(20);

        return view('admin.quizzes.confirmed-results', compact('attempts'));
    }

    /*
    |--------------------------------------------------------------------------
    | RIEPILOGO PER SINGOLO QUIZ CONFERMATO (admin only)
    |--------------------------------------------------------------------------
    */

    public function summary(Quiz $quiz, QuizSummaryService $summaries)
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        abort_unless($quiz->isConfirmed(), 404);

        $summary = $summaries->getSummary($quiz);

        return view('admin.quizzes.summary', [
            'quiz'        => $quiz,
            'kpi'         => $summary['kpi'],
            'enrollments' => $summary['enrollments'],
        ]);
    }

    public function exportResults(Quiz $quiz, QuizSummaryService $summaries)
    {
        abort_unless(auth()->user()->canEditQuiz(), 403);
        abort_unless($quiz->isConfirmed(), 403);

        $filename = 'risultati-' . Str::slug($quiz->title ?: ('quiz-' . $quiz->id))
            . '-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(new QuizResultsExport($quiz, $summaries), $filename);
    }

    /*
    |--------------------------------------------------------------------------
    | SCHEDULAZIONE ISCRIZIONI (admin only)
    |--------------------------------------------------------------------------
    */

    public function editSchedule(Quiz $quiz)
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        abort_unless($quiz->isConfirmed(), 404);

        return view('admin.quizzes.schedule', compact('quiz'));
    }

    public function updateSchedule(UpdateQuizScheduleRequest $request, Quiz $quiz)
    {
        try {
            $this->service->updateSchedule(
                $quiz,
                $request->input('enrollments_open_at'),
                $request->input('enrollments_close_at'),
            );
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()->route('admin.quizzes.index')
            ->with('success', 'Schedulazione iscrizioni aggiornata.');
    }

    /*
    |--------------------------------------------------------------------------
    | BULK ACTIONS METHODS
    |--------------------------------------------------------------------------
    */

    public function fillRandom(Quiz $quiz)
    {
        abort_unless(auth()->user()->canBulkQuiz(), 403);

        try {
            $result = $this->service->fillWithRandom($quiz);
        } catch (RuntimeException $e) {
            if (request()->wantsJson()) {
                return response()->json(['error' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage());
        }

        if (request()->wantsJson()) {
            if (!$result['ok']) {
                return response()->json(['error' => $result['error']], 422);
            }

            return response()->json($result);
        }

        if (!$result['ok']) {
            return back()->with('error', $result['error']);
        }

        return back()->with('success', "Aggiunte {$result['added']} domande random al quiz");
    }

    public function updateParams(Request $request, Quiz $quiz)
    {
        abort_unless(auth()->user()->canEditQuiz(), 403);

        if ($quiz->isLocked()) {
            return response()->json([
                'error' => 'Il quiz è confermato e non può essere modificato.',
            ], 422);
        }

        $data = $request->validate([
            'title'         => 'required|string|max:255',
            'max_questions' => 'required|integer|min:1|max:100',
        ]);

        $currentCount = $quiz->questions()->count();
        if ($data['max_questions'] < $currentCount) {
            return response()->json([
                'error' => "Il limite non può essere inferiore alle domande già presenti ($currentCount)",
            ], 422);
        }

        $quiz->update($data);

        return response()->json(['ok' => true, 'max_questions' => $quiz->fresh()->max_questions]);
    }

    public function bulkAdd(BulkQuizQuestionsRequest $request, Quiz $quiz)
    {
        try {
            $result = $this->service->bulkAddQuestions(
                $quiz,
                $request->input('mode', 'selection'),
                $request->input('ids', []),
                $request->input('category_id'),
            );
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        if (!$result['ok']) {
            return response()->json(['error' => $result['error']], 422);
        }

        return response()->json([
            'current' => $result['current'],
            'added'   => $result['added'],
        ]);
    }

    public function bulkRemove(BulkQuizQuestionsRequest $request, Quiz $quiz)
    {
        try {
            $current = $this->service->bulkRemoveQuestions(
                $quiz,
                $request->input('mode', 'selection'),
                $request->input('ids', []),
                $request->input('category_id'),
            );
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json(['current' => $current]);
    }
}
