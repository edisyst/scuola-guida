<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Question;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookmarkController extends Controller
{
    public function index(Request $request): View
    {
        $query = auth()->user()
            ->bookmarkedQuestions()
            ->with('category.translations');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('search')) {
            $query->where('question', 'like', '%' . $request->search . '%');
        }

        $bookmarks = $query->paginate(20)->withQueryString();
        $categories = Category::with('translations')->orderBy('name')->get();

        return view('bookmarks.index', compact('bookmarks', 'categories'));
    }

    public function destroy(Question $question): RedirectResponse
    {
        $detached = auth()->user()->bookmarkedQuestions()->detach($question->id);

        if ($detached === 0) {
            abort(403);
        }

        return redirect()->back()->with('success', __('flash.bookmark_removed'));
    }
}
