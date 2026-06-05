<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreQuestionTranslationRequest;
use App\Http\Requests\UpdateQuestionTranslationRequest;
use App\Models\Question;
use App\Services\QuestionTranslationService;
use Illuminate\Http\RedirectResponse;

class QuestionTranslationController extends Controller
{
    public function __construct(private QuestionTranslationService $service) {}

    public function store(StoreQuestionTranslationRequest $request, Question $question): RedirectResponse
    {
        abort_unless(auth()->user()->canEditQuestion(), 403);

        $this->service->upsert($question, $request->validated('locale'), $request->validated('text'));

        return redirect()
            ->route('admin.questions.edit', $question)
            ->with('success', __('flash.translation_saved'));
    }

    public function update(UpdateQuestionTranslationRequest $request, Question $question, string $locale): RedirectResponse
    {
        abort_unless(auth()->user()->canEditQuestion(), 403);

        $this->service->upsert($question, $locale, $request->validated('text'));

        return redirect()
            ->route('admin.questions.edit', $question)
            ->with('success', __('flash.translation_updated'));
    }

    public function destroy(Question $question, string $locale): RedirectResponse
    {
        abort_unless(auth()->user()->canEditQuestion(), 403);

        $this->service->delete($question, $locale);

        return redirect()
            ->route('admin.questions.edit', $question)
            ->with('success', __('flash.translation_deleted'));
    }
}
