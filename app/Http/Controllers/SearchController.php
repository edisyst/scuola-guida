<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Question;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $q = trim($request->input('q', ''));

        $questions = collect();
        $categories = collect();

        if ($q !== '') {
            $questions = Question::with('category')
                ->where('question', 'like', "%{$q}%")
                ->orderBy('question')
                ->get();

            $categories = Category::where('name', 'like', "%{$q}%")
                ->orderBy('name')
                ->get();
        }

        return view('search.results', compact('q', 'questions', 'categories'));
    }
}
