<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateQuizRequest;
use App\Http\Requests\StoreQuizRequest;
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

    public function store(StoreQuizRequest $request)
    {
        $data = $request->validated();
        $data['is_active'] = $request->has('is_active');

        $quiz = Quiz::create($data);

        // 🔥 collega domande
        if (!empty($data['questions'])) {
            if (count($data['questions']) > $quiz->max_questions) {
                return back()->withErrors([
                    'questions' => 'Superato limite massimo domande'
                ]);
            }

            $quiz->questions()->sync($data['questions']);
        }

        clearAdminBadgesCache();

        return redirect()->route('admin.quizzes.index')
            ->with('success', 'Quiz creato');
    }

    public function edit(Quiz $quiz)
    {
        $questionsCount = $currentCount = $quiz->questions()->count();
        $max = $quiz->max_questions;

        $questions = Question::limit(200)->get();

        return view('admin.quizzes.edit', compact('quiz', 'questions', 'questionsCount', 'currentCount', 'max'));
    }

    public function update(UpdateQuizRequest $request, Quiz $quiz)
    {
        $validated = $request->validated();
        $validated['is_active'] = $request->has('is_active');

        $questions = $request->validate([
            'questions'   => 'nullable|array',
            'questions.*' => 'exists:questions,id',
        ])['questions'] ?? [];

        $quiz->update($validated);
        $quiz->questions()->sync($questions);

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

    public function questionsList(Quiz $quiz)
    {
        return $quiz->questions()
            ->orderBy('pivot_order')
            ->get(['id', 'question']);
    }

    public function reorder(Request $request, Quiz $quiz)
    {
        $ids = $request->input('ids'); // array ordinato

        foreach ($ids as $index => $id) {
            $quiz->questions()->updateExistingPivot($id, [
                'order' => $index
            ]);
        }

        return response()->json(['success' => true]);
    }

    public function questionsData(Request $request, Quiz $quiz)
    {
        $query = Question::with('category')
            ->select('questions.*');

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        $quizQuestionIds = $quiz->questions()->pluck('questions.id')->toArray();

        return DataTables::of($query)

            ->addColumn('category', function ($q) {
                return $q->category->name ?? '-';
            })

            ->addColumn('is_in_quiz', function ($q) use ($quizQuestionIds) {
                return in_array($q->id, $quizQuestionIds) ? 'added' : 'pending';
            })

            ->addColumn('status', function ($q) use ($quizQuestionIds) {
                return in_array($q->id, $quizQuestionIds)
                    ? '<span class="badge badge-success">✔ Nel quiz</span>'
                    : '<span class="badge badge-secondary">Non presente</span>';
            })

            ->addColumn('action', function ($q) use ($quizQuestionIds) {
                $exists = in_array($q->id, $quizQuestionIds);
                $questionText = htmlspecialchars($q->question, ENT_QUOTES, 'UTF-8');

                return $exists
                    ? '<button class="btn btn-sm btn-danger btn-remove" data-id="'.$q->id.'" data-text="'.$questionText.'">Rimuovi</button>'
                    : '<button class="btn btn-sm btn-success btn-add" data-id="'.$q->id.'" data-text="'.$questionText.'">Aggiungi</button>';
            })

            ->addColumn('in_quiz', function ($q) use ($quizQuestionIds) {
                return in_array($q->id, $quizQuestionIds);
            })

            ->rawColumns(['status', 'action'])
            ->make(true);
    }

    public function manageQuestions(Quiz $quiz)
    {
        $quiz->load('questions');

        $questions = Question::with('category')->get();

        $currentCount = $quiz->questions()->count();
        $max = $quiz->max_questions;

        return view('admin.quizzes.questions', compact('quiz', 'questions', 'currentCount', 'max'));
    }

    public function addQuestion(Request $request, Quiz $quiz)
    {
        $request->validate([
            'question_id' => 'required|exists:questions,id',
        ]);

        if ($quiz->hasReachedLimit()) {
            return response()->json(['error' => 'Limite massimo raggiunto'], 422);
        }

        $quiz->questions()->syncWithoutDetaching([$request->question_id]);

        return response()->json(['current' => $quiz->questions()->count()]);
    }

    public function removeQuestion(Request $request, Quiz $quiz)
    {
        $request->validate([
            'question_id' => 'required|exists:questions,id',
        ]);

        $quiz->questions()->detach($request->question_id);

        return response()->json(['current' => $quiz->questions()->count()]);
    }

    public function createRandom()
    {
        $max = 30;

        $ids = Question::inRandomOrder()->limit($max)->pluck('id');

        $quiz = Quiz::create([
            'title'         => 'QUIZ RANDOM NR.',
            'max_questions' => $max,
        ]);

        $quiz->title = 'QUIZ RANDOM NR. ' . $quiz->id;
        $quiz->save();

        $quiz->questions()->attach($ids);

        return redirect()
            ->route('admin.quizzes.index')
            ->with('success', 'Quiz creato con ' . $ids->count() . ' domande');
    }

    public function randomPlay()
    {
        $questions = Question::inRandomOrder()->limit(10)->get();

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

        // 🔥 count attuale
        $current = $quiz->questions()->count();

        // 🔥 recupero IDs da aggiungere
        if ($request->mode === 'all') {

            $query = \App\Models\Question::query();

            if ($request->category_id) {
                $query->where('category_id', $request->category_id);
            }

            $ids = $query->pluck('id');

        } else {
            $ids = collect($request->ids ?? []);
        }

        // 🔥 niente selezione
        if ($ids->isEmpty()) {
            return response()->json([
                'error' => 'Nessuna selezione'
            ], 422);
        }

        // 🔥 IDs già presenti nel quiz
        $existingIds = $quiz->questions()->pluck('questions.id');

        // 🔥 filtro duplicati
        $idsToInsert = $ids->diff($existingIds);

        if ($idsToInsert->isEmpty()) {
            return response()->json([
                'error' => 'Tutte le domande selezionate sono già presenti nel quiz'
            ], 422);
        }

        // 🔥 spazio disponibile
        $available = $max - $current;

        if ($available <= 0) {
            return response()->json([
                'error' => 'Limite massimo raggiunto'
            ], 422);
        }

        // 🔥 limito al massimo consentito
        $idsToInsert = $idsToInsert->take($available);

        $quiz->questions()->attach($idsToInsert);

        return response()->json([
            'current' => $quiz->questions()->count(),
            'added'   => $idsToInsert->count(),
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

        return response()->json([
            'current' => $quiz->questions()->count(),
//             'success' => true,
        ]);
    }
}
