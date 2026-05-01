<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Quiz;
use App\Models\QuizResult;
use App\Models\Question;
use App\Services\QuizService;
use Yajra\DataTables\Facades\DataTables;

class QuizController extends Controller
{
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
        $questions = Question::limit(200)->get(); // evita carichi enormi

        return view('admin.quizzes.create', compact('questions'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'         => 'nullable', //|string forse??? o forse si può togliere?
            'questions'     => 'nullable|array',
            'questions.*'   => 'exists:questions,id',
            'max_questions' => 'required|integer|min:1|max:100',
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
            'max_questions' => 'required|integer|min:1|max:100',
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

    public function questionsData(Request $request, Quiz $quiz)
    {
//        dd(99);
        $query = Question::with('category')
            ->select('questions.*');

        // filtro categoria
        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        return DataTables::of($query)

            ->addColumn('category', function ($q) {
                return $q->category->name ?? '-';
            })

            ->addColumn('status', function ($q) use ($quiz) {

                $exists = $quiz->questions()
                    ->where('question_id', $q->id)
                    ->exists();

                return $exists
                    ? '<span class="badge badge-success">✔ Nel quiz</span>'
                    : '<span class="badge badge-secondary">Non presente</span>';
            })

            ->addColumn('action', function ($q) use ($quiz) {

                $exists = $quiz->questions()
                    ->where('question_id', $q->id)
                    ->exists();

                if ($exists) {
                    return '
                    <button class="btn btn-sm btn-danger btn-remove"
                        data-id="'.$q->id.'">
                        Rimuovi
                    </button>
                ';
                }

                return '
                <button class="btn btn-sm btn-success btn-add"
                    data-id="'.$q->id.'">
                    Aggiungi
                </button>
            ';
            })

            ->rawColumns(['status', 'action'])
            ->make(true);
    }

    public function manageQuestions(Quiz $quiz)
    {
        $quiz->load('questions');

        $questions = Question::with('category')->get();

        return view('admin.quizzes.questions', compact('quiz', 'questions'));
    }

    public function addQuestion(Request $request, Quiz $quiz)
    {
        if ($quiz->hasReachedLimit()) {
            return response()->json([
                'error' => 'Limite massimo raggiunto'
            ], 422);
        }

//        $request->validate([
//            'question_id' => 'required|exists:questions,id'
//        ]);

        $quiz->questions()->syncWithoutDetaching([
            $request->question_id
        ]);

        return back()->with('success', 'Domanda aggiunta');
    }

    public function removeQuestion(Request $request, Quiz $quiz)
    {
        $request->validate([
            'question_id' => 'required|exists:questions,id'
        ]);

        $quiz->questions()->detach($request->question_id);

        return back()->with('success', 'Domanda rimossa');
    }

    public function createRandom()
    {
        $max = $quiz->max_questions;

        $quiz = Quiz::create([
            'title' => 'QUIZ NR.',
            'max_questions' => 30
        ]);

        $ids = Question::inRandomOrder()
            ->limit($max)
            ->pluck('id');

        $quiz->questions()->attach($ids);

        return redirect()
            ->route('admin.quizzes.index')
            ->with('success', 'Quiz creato con '.$ids->count().' domande');
    }

    public function randomPlay()
    {
        $ids = Question::inRandomOrder()->limit(10)->pluck('id');
        $questions = Question::whereIn('id', $ids)->get();

        return view('quiz.play', compact('questions'));
    }

    public function play(Quiz $quiz)
    {
        $questions = $quiz->questions()->get();

        return view('quiz.play', [
            'quiz' => $quiz,
            'questionsJson' => $questions->map(function ($q) {
                return [
                    'id' => $q->id,
                    'text' => $q->question,
                    'image' => $q->image ? asset('storage/'.$q->image) : null,
                    'correct' => $q->is_true,
                ];
            })
        ]);
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

    /*
    |--------------------------------------------------------------------------
    | BULK ACTIONS METHODS
    |--------------------------------------------------------------------------
    */

    public function bulkAdd(Request $request, Quiz $quiz)
    {
        $max = $quiz->max_questions;
        $current = $quiz->questions()->count();

        if ($request->mode === 'all') {

            $query = Question::query();

            if ($request->category_id) {
                $query->where('category_id', $request->category_id);
            }

            $ids = $query->pluck('id');

        } else {
            $ids = collect($request->ids ?? []);
        }

        // 🔥 calcolo quanti posso aggiungere
        $available = $max - $current;

        if ($available <= 0) {
            return response()->json([
                'error' => 'Limite massimo raggiunto'
            ], 422);
        }

        // 🔥 limito gli ID
        $ids = collect($ids)->take($available);

        $quiz->questions()->syncWithoutDetaching($ids);

        return response()->json([
            'success' => true,
            'added' => $ids->count()
        ]);
    }

    public function bulkRemove(Request $request, Quiz $quiz)
    {
        if ($request->mode === 'all') {

            $query = Question::query();

            if ($request->category_id) {
                $query->where('category_id', $request->category_id);
            }

            $ids = $query->pluck('id');

        } else {
            $ids = $request->ids ?? [];
        }

        $quiz->questions()->detach($ids);

        return response()->json(['success' => true]);
    }
}
