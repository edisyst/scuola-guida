<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryMaterialRequest;
use App\Http\Requests\UpdateCategoryMaterialRequest;
use App\Models\Category;
use App\Models\CategoryMaterial;
use App\Services\CategoryMaterialService;
use Illuminate\Http\Request;

class CategoryMaterialController extends Controller
{
    public function __construct(private CategoryMaterialService $service) {}

    public function index(Category $category)
    {
        abort_unless(auth()->user()->canEditCategory(), 403);

        $materials = $category->materials()->with('creator')->ordered()->get();

        return view('admin.categories.materials.index', compact('category', 'materials'));
    }

    public function create(Category $category)
    {
        abort_unless(auth()->user()->canEditCategory(), 403);

        return view('admin.categories.materials.create', compact('category'));
    }

    public function store(StoreCategoryMaterialRequest $request, Category $category)
    {
        abort_unless(auth()->user()->canEditCategory(), 403);

        $this->service->create($category, $request->validated(), $request->file('file'));

        return redirect()
            ->route('admin.categories.materials.index', $category)
            ->with('success', __('flash.material_created'));
    }

    public function edit(Category $category, CategoryMaterial $material)
    {
        abort_unless(auth()->user()->canEditCategory(), 403);

        return view('admin.categories.materials.edit', compact('category', 'material'));
    }

    public function update(UpdateCategoryMaterialRequest $request, Category $category, CategoryMaterial $material)
    {
        abort_unless(auth()->user()->canEditCategory(), 403);

        $this->service->update($material, $request->validated(), $request->file('file'));

        return redirect()
            ->route('admin.categories.materials.index', $category)
            ->with('success', __('flash.material_updated'));
    }

    public function destroy(Category $category, CategoryMaterial $material)
    {
        abort_unless(auth()->user()->canEditCategory(), 403);

        $this->service->delete($material);

        return back()->with('success', __('flash.material_deleted'));
    }

    public function reorder(Request $request, Category $category)
    {
        abort_unless(auth()->user()->canEditCategory(), 403);

        $request->validate(['ids' => 'required|array']);

        $this->service->reorder($request->input('ids'));

        return response()->json(['ok' => true]);
    }
}
