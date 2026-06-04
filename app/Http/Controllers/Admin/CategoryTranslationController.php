<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryTranslationRequest;
use App\Http\Requests\UpdateCategoryTranslationRequest;
use App\Models\Category;
use App\Services\CategoryTranslationService;
use Illuminate\Http\RedirectResponse;

class CategoryTranslationController extends Controller
{
    public function __construct(private CategoryTranslationService $service) {}

    public function store(StoreCategoryTranslationRequest $request, Category $category): RedirectResponse
    {
        abort_unless(auth()->user()->canEditCategory(), 403);

        $this->service->upsert($category, $request->validated('locale'), $request->validated('name'));

        return redirect()
            ->route('admin.categories.edit', $category)
            ->with('success', 'Traduzione salvata.');
    }

    public function update(UpdateCategoryTranslationRequest $request, Category $category, string $locale): RedirectResponse
    {
        abort_unless(auth()->user()->canEditCategory(), 403);

        $this->service->upsert($category, $locale, $request->validated('name'));

        return redirect()
            ->route('admin.categories.edit', $category)
            ->with('success', 'Traduzione aggiornata.');
    }

    public function destroy(Category $category, string $locale): RedirectResponse
    {
        abort_unless(auth()->user()->canEditCategory(), 403);

        $this->service->delete($category, $locale);

        return redirect()
            ->route('admin.categories.edit', $category)
            ->with('success', 'Traduzione eliminata.');
    }
}
