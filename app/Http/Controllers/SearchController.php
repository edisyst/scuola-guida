<?php

namespace App\Http\Controllers;

use App\Services\SearchService;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __construct(private SearchService $service) {}

    public function index(Request $request)
    {
        $q       = trim($request->input('q', ''));
        $results = $this->service->search($q);

        return view('search.results', [
            'q'          => $q,
            'questions'  => $results['questions'],
            'categories' => $results['categories'],
        ]);
    }
}
