<?php

namespace App\Http\Controllers\Viewer;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\SpacedRepetitionService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SmartReviewController extends Controller
{
    public function __construct(private SpacedRepetitionService $service) {}

    public function index(): View
    {
        abort_unless(auth()->user()->isViewer(), 403);

        $user       = auth()->user();
        $stats      = $this->service->getStats($user);
        $upcoming   = $this->service->getUpcomingCount($user);
        $categories = Category::orderBy('name')->get();

        return view('smart-review.index', compact('stats', 'upcoming', 'categories'));
    }

    public function session(Request $request): View
    {
        abort_unless(auth()->user()->isViewer(), 403);

        $categoryId = $request->filled('category_id') ? (int) $request->query('category_id') : null;

        return view('smart-review.session', compact('categoryId'));
    }
}
