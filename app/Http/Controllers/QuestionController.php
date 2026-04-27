<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Services\QuestionService;
use App\Http\Requests\StoreQuestionRequest;
use App\Http\Requests\UpdateQuestionRequest;
use Illuminate\Support\Facades\Cache;
use App\Filters\QuestionFilter;
use App\Exports\QuestionsExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\QuestionsImport;

class QuestionController extends Controller
{
    public function __construct(private QuestionService $service) {}

    public function export()
    {
        return Excel::download(new QuestionsExport, 'questions.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,csv'
        ]);

        Excel::import(new QuestionsImport, $request->file('file'));

        return back()->with('success', 'Import completato');
    }

    public function template()
    {
        $data = [
            ['ID', 'Categoria', 'Domanda', 'Risposta', 'Immagine'],
            ['', 'Segnaletica', 'Esempio domanda', 'VERO', ''],
        ];

        return Excel::download(new class($data) implements \Maatwebsite\Excel\Concerns\FromArray {
            private $data;
            public function __construct($data) { $this->data = $data; }
            public function array(): array { return $this->data; }
        }, 'template_questions.xlsx');
    }

    public function bulkDelete(Request $request)
    {
        Question::whereIn('id', $request->ids)->delete();

        return response()->json(['success' => true]);
    }

    public function data(Request $request)
    {
        $query = Question::with('category:id,name');

        // 🔍 ricerca globale
        if ($search = $request->input('search.value')) {
            $query->where(function ($q) use ($search) {
                $q->where('question', 'like', "%{$search}%")
                    ->orWhereHas('category', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // 🔥 FILTRO CATEGORIA
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // 🔥 FILTRO TRUE/FALSE
        if ($request->filled('is_true')) {
            $query->where('is_true', $request->is_true);
        }

        // 🔥 FILTRO IMMAGINE
        if ($request->filled('has_image')) {
            $query->whereNotNull('image');
        }

        $total = Question::count();
        $filtered = $query->count();

        $data = $query
            ->skip($request->start)
            ->take($request->length)
            ->latest()
            ->get();

        return response()->json([
            'draw' => intval($request->draw),
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $data->map(function ($q) {

                return [
                    'id' => $q->id,
                    'category' => $q->category->name,
                    'question' => \Str::limit($q->question, 50),

                    'is_true' => $q->is_true
                        ? '<span class="badge badge-success">Vero</span>'
                        : '<span class="badge badge-danger">Falso</span>',

                    'image' => $q->image
                        ? '<img src="'.(str_starts_with($q->image, 'http') ? $q->image : asset('storage/'.$q->image)).'" width="50">'
                        : '',

                    'actions' => view('admin.questions.partials.actions', compact('q'))->render(),

                    'checkbox' => '<input type="checkbox" class="row-checkbox" value="'.$q->id.'">',
                ];
            }),
        ]);
    }

    public function index(Request $request, QuestionFilter $filter)
    {
        $questions = Question::query()
            ->with('category:id,name');

        $questions = $filter->apply($questions)
            ->latest()
            ->get();

        $categories = Cache::remember('categories_list', 3600, function () {
            return Category::select('id', 'name')->get();
        });

        return view('admin.questions.index', compact('questions', 'categories'));
    }

    public function create()
    {
        $categories = Category::pluck('name', 'id'); // per select

        return view('admin.questions.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'question'    => 'required|string',
            'image'       => 'nullable|image|max:2048',
        ]);

        $data['is_true'] = $request->has('is_true');

        // upload immagine
        $image = $this->handleImageUpload($request);

        if ($image) {
            $data['image'] = $image;
        }

        Question::create($data);

        return redirect()->route('questions.index')
            ->with('success', 'Domanda creata');
    }

    public function edit(Question $question)
    {
        $categories = Category::pluck('name', 'id');

        return view('admin.questions.edit', compact('question', 'categories'));
    }

    public function update(Request $request, Question $question)
    {
        $data = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'question'    => 'required|string',
            'image'       => 'nullable|image|max:2048',
        ]);

        $data['is_true'] = $request->has('is_true');

        $image = $this->handleImageUpload($request, $question);

        if ($image) {
            $data['image'] = $image;
        }

        $question->update($data);

        return redirect()->route('questions.index')
            ->with('success', 'Domanda aggiornata');
    }

    public function destroy(Question $question)
    {
        $this->service->delete($question);

        return back()->with('success', 'Domanda eliminata');
    }

    private function handleImageUpload($request, $question = null)
    {
        if ($request->hasFile('image')) {

            // se sto aggiornando e c'è già un'immagine → cancello
            if ($question && $question->image) {
                \Storage::disk('public')->delete($question->image);
            }

            return $request->file('image')->store('questions', 'public');
        }

        return null;
    }
}
