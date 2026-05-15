<?php

namespace App\Http\Controllers;

use App\DataTables\QuizQuestionsDataTable;
use App\Http\Requests\BulkQuizQuestionsRequest;
use App\Http\Requests\StoreQuizRequest;
use App\Http\Requests\UpdateQuizRequest;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizResult;
use App\Services\QuizService;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    public function __construct(private QuizService $service) {}

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

    public function edit(Quiz $quiz)
    {
        abort_unless(auth()->user()->canEditQuiz(), 403);

        $currentCount = $quiz->questions()->count();
        $max = $quiz->max_questions;
        $questions = Question::limit(200)->get();

        return view('admin.quizzes.edit', [
            'quiz'           => $quiz,
            'questions'      => $questions,
            'questionsCount' => $currentCount,
            'currentCount'   => $currentCount,
            'max'            => $max,
        ]);
    }

    public function update(UpdateQuizRequest $request, Quiz $quiz)
    {
        $this->service->update($quiz, $request->validated());

        return redirect()->route('admin.quizzes.index')
            ->with('success', 'Quiz aggiornato');
    }

    public function destroy(Quiz $quiz)
    {
        abort_unless(auth()->user()->canDeleteQuiz(), 403);

        $quiz->delete();

        return back()->with('success', 'Quiz eliminato');
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
        $this->service->reorderQuestions($quiz, $request->input('ids', []));

        return response()->json(['success' => true]);
    }

    public function questionsData(Request $request, Quiz $quiz, QuizQuestionsDataTable $dataTable)
    {
        return $dataTable->response($request, $quiz);
    }

    public function manageQuestions(Quiz $quiz)
    {
        $quiz->load('questions');

        $questions    = Question::with('category')->get();
        $currentCount = $quiz->questions()->count();
        $max          = $quiz->max_questions;

        return view('admin.quizzes.questions', compact('quiz', 'questions', 'currentCount', 'max'));
    }

    public function addQuestion(Request $request, Quiz $quiz)
    {
        $result = $this->service->addQuestion($quiz, (int) $request->question_id);

        if (!$result['ok']) {
            return response()->json(['error' => $result['error']], 422);
        }

        return response()->json(['current' => $result['current']]);
    }

    public function removeQuestion(Request $request, Quiz $quiz)
    {
        $current = $this->service->removeQuestion($quiz, (int) $request->question_id);

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

    public function randomPlay()
    {
        $ids       = Question::inRandomOrder()->limit(10)->pluck('id');
        $questions = Question::whereIn('id', $ids)->get();

        return view('quiz.play', compact('questions'));
    }

    public function play(Quiz $quiz)
    {
        $session = $this->service->startPlay($quiz, auth()->id());

        return view('quiz.play', [
            'quiz'          => $quiz,
            'timeLimit'     => $quiz->time_limit,
            'maxErrors'     => $quiz->max_errors,
            'attemptId'     => $session['attempt']->id,
            'questionsJson' => $session['questions_json'],
        ]);
    }

    public function submit(Request $request)
    {
        $answers = $request->input('answers', []);
        $score   = $this->service->calculateScore($answers);

        QuizResult::create([
            'user_id' => auth()->id(),
            'score'   => $score,
            'total'   => count($answers),
        ]);

        return response()->json(['score' => $score]);
    }

    public function results()
    {
        $results = QuizResult::with('user')->latest()->get();

        return view('quiz.results', compact('results'));
    }

    /*
    |--------------------------------------------------------------------------
    | BULK ACTIONS METHODS
    |--------------------------------------------------------------------------
    */

    public function bulkAdd(BulkQuizQuestionsRequest $request, Quiz $quiz)
    {
        $result = $this->service->bulkAddQuestions(
            $quiz,
            $request->input('mode', 'selection'),
            $request->input('ids', []),
            $request->input('category_id'),
        );

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
        $current = $this->service->bulkRemoveQuestions(
            $quiz,
            $request->input('mode', 'selection'),
            $request->input('ids', []),
            $request->input('category_id'),
        );

        return response()->json(['current' => $current]);
    }
}
