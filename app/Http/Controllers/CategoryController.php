<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CategoryController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | CRUD
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $categories = Category::withCount('questions')->get();

        return view('admin.categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.categories.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        Category::create($data);
        Cache::forget('categories_list'); // sarebbe da creare l'helper anche di questo
        clearAdminBadgesCache();

        return redirect()->route('admin.categories.index')
            ->with('success', 'Categoria creata');
    }

    public function edit(Category $category)
    {
        return view('admin.categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
//            'slug' => 'required|string|unique:categories,slug,' . $category->id,
        ]);

        $category->update($data);
        Cache::forget('categories_list'); // sarebbe da creare l'helper anche di questo
        clearAdminBadgesCache();

        return redirect()->route('admin.categories.index')
            ->with('success', 'Categoria aggiornata');
    }

    public function destroy(Category $category)
    {
        $category->delete();
        Cache::forget('categories_list'); // sarebbe da creare l'helper anche di questo
        clearAdminBadgesCache();

        return back()->with('success', 'Categoria eliminata');
    }
}
