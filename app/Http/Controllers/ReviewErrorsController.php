<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Question;
use App\Services\ReviewErrorsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewErrorsController extends Controller
{
    public function __construct(private ReviewErrorsService $service) {}

    public function index(Request $request): View
    {
        abort_unless(auth()->user()->isViewer(), 403);

        $request->validate([
            'category_id'   => 'nullable|integer|exists:categories,id',
            'last_attempts' => 'nullable|integer|between:5,50',
            'show_learned'  => 'nullable|boolean',
        ]);

        $user         = auth()->user();
        $categoryId   = $request->filled('category_id') ? (int) $request->input('category_id') : null;
        $lastAttempts = $request->filled('last_attempts') ? (int) $request->input('last_attempts') : 20;
        $showLearned  = $request->boolean('show_learned');

        if ($showLearned) {
            $errors = $this->service->getLearned($user, $categoryId)
                ->map(fn ($q) => [
                    'question'      => $q,
                    'error_count'   => null,
                    'last_wrong_at' => null,
                    'category'      => $q->category,
                ]);
        } else {
            $errors = $this->service->getErrors($user, $categoryId, $lastAttempts);
        }

        $learnedCount = $this->service->getLearned($user)->count();
        $categories   = Category::orderBy('name')->get();

        return view('review-errors.index', compact(
            'errors', 'categories', 'categoryId', 'lastAttempts', 'showLearned', 'learnedCount'
        ));
    }

    public function markLearned(Question $question): RedirectResponse
    {
        abort_unless(auth()->user()->isViewer(), 403);

        $this->service->markAsLearned(auth()->user(), $question->id);

        return redirect()->back()->with('success', 'Domanda marcata come imparata.');
    }

    public function unmarkLearned(Question $question): RedirectResponse
    {
        abort_unless(auth()->user()->isViewer(), 403);

        $this->service->unmarkAsLearned(auth()->user(), $question->id);

        return redirect()->back()->with('success', 'Domanda reinserita tra gli errori da rivedere.');
    }
}
