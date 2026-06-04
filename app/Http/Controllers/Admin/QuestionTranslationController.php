<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreQuestionTranslationRequest;
use App\Http\Requests\UpdateQuestionTranslationRequest;
use App\Models\Question;
use App\Services\QuestionTranslationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class QuestionTranslationController extends Controller
{
    public function __construct(private QuestionTranslationService $service) {}

    public function index(Question $question): View
    {
        abort_unless(auth()->user()->canEditQuestion(), 403);

        $translations = $this->service->getForQuestion($question);

        // Lingue d'esame ancora disponibili (escluse quelle già tradotte e il default).
        $existing  = $translations->pluck('locale')->all();
        $default   = config('locales.default', 'it');
        $available = collect(config('locales.exam', []))
            ->except(array_merge($existing, [$default]));

        return view('admin.questions.translations', compact('question', 'translations', 'available'));
    }

    public function store(StoreQuestionTranslationRequest $request, Question $question): RedirectResponse
    {
        abort_unless(auth()->user()->canEditQuestion(), 403);

        $this->service->upsert($question, $request->validated('locale'), $request->validated('text'));

        return redirect()
            ->route('admin.questions.translations.index', $question)
            ->with('success', 'Traduzione salvata con successo.');
    }

    public function update(UpdateQuestionTranslationRequest $request, Question $question, string $locale): RedirectResponse
    {
        abort_unless(auth()->user()->canEditQuestion(), 403);

        $this->service->upsert($question, $locale, $request->validated('text'));

        return redirect()
            ->route('admin.questions.translations.index', $question)
            ->with('success', 'Traduzione aggiornata con successo.');
    }

    public function destroy(Question $question, string $locale): RedirectResponse
    {
        abort_unless(auth()->user()->canEditQuestion(), 403);

        $this->service->delete($question, $locale);

        return redirect()
            ->route('admin.questions.translations.index', $question)
            ->with('success', 'Traduzione eliminata.');
    }
}
