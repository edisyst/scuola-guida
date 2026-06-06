<?php

namespace App\Http\Controllers;

use App\DataTables\QuestionsDataTable;
use App\Exports\QuestionsExport;
use App\Http\Requests\BulkDeleteQuestionsRequest;
use App\Http\Requests\ImportMitQuestionsRequest;
use App\Http\Requests\ImportQuestionsRequest;
use App\Http\Requests\StoreQuestionRequest;
use App\Http\Requests\UpdateQuestionRequest;
use App\Imports\QuestionsImport;
use App\Models\Category;
use App\Models\LicenseType;
use App\Models\Question;
use App\Services\LicenseTypeService;
use App\Services\MitImportService;
use App\Services\QuestionService;
use App\Services\QuestionTranslationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
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
            ->with('success', __('flash.question_created'));
    }

    public function edit(Question $question, QuestionTranslationService $translationService)
    {
        $categories   = Category::pluck('name', 'id');
        $translations = $translationService->getForQuestion($question);
        $existing     = $translations->pluck('locale')->all();
        $default      = config('locales.default', 'it');
        $available    = collect(config('locales.exam', []))
            ->except(array_merge($existing, [$default]));

        return view('admin.questions.edit', compact('question', 'categories', 'translations', 'available'));
    }

    public function update(UpdateQuestionRequest $request, Question $question)
    {
        $this->service->update($question, $request->validated(), $request->file('image'));

        return redirect()->route('admin.questions.index')
            ->with('success', __('flash.question_updated'));
    }

    public function destroy(Question $question)
    {
        abort_unless(auth()->user()->canDeleteQuestion(), 403);

        $this->service->delete($question);

        return back()->with('success', __('flash.question_deleted'));
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

        return back()->with('success', __('flash.questions_imported'));
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

    /*
    |--------------------------------------------------------------------------
    | IMPORT MIT
    |--------------------------------------------------------------------------
    */

    public function showMitImport(LicenseTypeService $licenseTypeService): View
    {
        abort_unless(auth()->user()->canCreateQuestion(), 403);

        return view('admin.questions.mit-import', [
            'topicMap'    => config('mit_import.topic_map'),
            'configPath'  => config_path('mit_import.php'),
            'licenseTypes' => $licenseTypeService->all(),
            'defaultType' => LicenseType::where('code', config('mit_import.default_license_type_code'))->first(),
        ]);
    }

    public function storeMitImport(ImportMitQuestionsRequest $request, MitImportService $service): RedirectResponse
    {
        $licenseType = LicenseType::findOrFail($request->integer('license_type_id'));
        $stored   = $request->file('file')->store('tmp/mit-import');
        $filePath = Storage::disk('local')->path($stored);

        try {
            $result = $service->import(
                filePath:       $filePath,
                licenseType:    $licenseType,
                dryRun:         $request->boolean('dry_run'),
                updateExisting: $request->boolean('update_existing'),
                topicFilter:    $request->filled('topic_filter') ? $request->integer('topic_filter') : null,
                onProgress:     null,
            );
        } finally {
            Storage::delete($stored);
        }

        $summary = "Importazione completata per {$licenseType->name}: {$result->imported} inserite, {$result->updated} aggiornate";

        if (!empty($result->errors)) {
            session(['mit_import_errors' => $result->errors]);
            return redirect()->back()->with('warning', $summary . ' | Errori: ' . count($result->errors));
        }

        return redirect()->back()->with('success', $summary);
    }
}
