<?php

namespace App\Http\Controllers;

use App\DataTables\QuestionsDataTable;
use App\Exports\QuestionsExport;
use App\Http\Requests\BulkDeleteQuestionsRequest;
use App\Http\Requests\ImportQuestionsRequest;
use App\Http\Requests\StoreQuestionRequest;
use App\Http\Requests\UpdateQuestionRequest;
use App\Imports\QuestionsImport;
use App\Models\Category;
use App\Models\Question;
use App\Services\QuestionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Facades\Excel;

class QuestionController extends Controller
{
    public function __construct(private QuestionService $service) {}

    /*
    |--------------------------------------------------------------------------
    | CRUD
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        // $questions rimossa: la tabella è popolata via AJAX (/questions/data).
        // Caricare tutte le domande qui era un N+K inutile. Vedi W-3.
        $categories = Cache::remember(
            'categories_list',
            3600,
            fn () => Category::select('id', 'name')->get()
        );

        return view('admin.questions.index', compact('categories'));
    }

    public function create()
    {
        $categories = Category::pluck('name', 'id');

        return view('admin.questions.create', compact('categories'));
    }

    public function store(StoreQuestionRequest $request)
    {
        $this->service->create($request->validated(), $request->file('image'));

        return redirect()->route('admin.questions.index')
            ->with('success', 'Domanda creata');
    }

    public function edit(Question $question)
    {
        $categories = Category::pluck('name', 'id');

        return view('admin.questions.edit', compact('question', 'categories'));
    }

    public function update(UpdateQuestionRequest $request, Question $question)
    {
        $this->service->update($question, $request->validated(), $request->file('image'));

        return redirect()->route('admin.questions.index')
            ->with('success', 'Domanda aggiornata');
    }

    public function destroy(Question $question)
    {
        abort_unless(auth()->user()->canDeleteQuestion(), 403);

        $this->service->delete($question);

        return back()->with('success', 'Domanda eliminata');
    }

    /*
    |--------------------------------------------------------------------------
    | ALTRI METODI
    |--------------------------------------------------------------------------
    */

    public function data(Request $request, QuestionsDataTable $dataTable)
    {
        return response()->json($dataTable->response($request));
    }

    public function bulkDelete(BulkDeleteQuestionsRequest $request)
    {
        $this->service->bulkDelete($request->validated('ids'));

        return response()->json(['success' => true]);
    }

    /*
    |--------------------------------------------------------------------------
    | EXPORT-IMPORT EXCEL
    |--------------------------------------------------------------------------
    */

    public function export()
    {
        return Excel::download(new QuestionsExport, 'questions.xlsx');
    }

    public function import(ImportQuestionsRequest $request)
    {
        Excel::import(new QuestionsImport, $request->file('file'));

        return back()->with('success', 'Import completato');
    }

    public function template()
    {
        $data = [
            ['ID', 'Categoria', 'Domanda', 'Risposta', 'Immagine'],
            ['', 'Segnaletica', 'Esempio domanda', 'VERO', ''],
        ];

        return Excel::download(new class($data) implements FromArray {
            public function __construct(private array $data) {}
            public function array(): array { return $this->data; }
        }, 'template_questions.xlsx');
    }
}
