<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\CategoryTranslationService;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::withCount('questions')->get();

        return view('admin.categories.index', compact('categories'));
    }

    public function create()
    {
        abort_unless(auth()->user()->canCreateCategory(), 403);

        return view('admin.categories.create');
    }

    public function store(StoreCategoryRequest $request)
    {
        Category::create($request->validated());

        return redirect()->route('admin.categories.index')
            ->with('success', __('flash.category_created'));
    }

    public function edit(Category $category, CategoryTranslationService $service)
    {
        abort_unless(auth()->user()->canEditCategory(), 403);

        $translations = $service->getForCategory($category);
        $existing     = $translations->pluck('locale')->all();
        // 'it' è sempre la fonte di verità per i contenuti, indipendente da APP_LOCALE.
        $available    = collect(config('locales.exam', []))
            ->except(array_merge($existing, ['it']));

        return view('admin.categories.edit', compact('category', 'translations', 'available'));
    }

    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $category->update($request->validated());

        return redirect()->route('admin.categories.index')
            ->with('success', __('flash.category_updated'));
    }

    public function destroy(Category $category)
    {
        abort_unless(auth()->user()->canDeleteCategory(), 403);

        $category->delete();

        return back()->with('success', __('flash.category_deleted'));
    }
}
