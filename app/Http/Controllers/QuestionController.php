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

class QuestionController extends Controller
{
    public function __construct(private QuestionService $service) {}

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
