<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;

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
            ->with('success', 'Categoria creata');
    }

    public function edit(Category $category)
    {
        abort_unless(auth()->user()->canEditCategory(), 403);

        return view('admin.categories.edit', compact('category'));
    }

    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $category->update($request->validated());

        return redirect()->route('admin.categories.index')
            ->with('success', 'Categoria aggiornata');
    }

    public function destroy(Category $category)
    {
        abort_unless(auth()->user()->canDeleteCategory(), 403);

        $category->delete();

        return back()->with('success', 'Categoria eliminata');
    }
}
