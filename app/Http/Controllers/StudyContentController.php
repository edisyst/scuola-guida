<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudyContentRequest;
use App\Http\Requests\UpdateStudyContentRequest;
use App\Models\Category;
use App\Models\DrivingModule;
use App\Models\StudyContent;
use App\Services\StudyContentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class StudyContentController extends Controller
{
    public function __construct(private readonly StudyContentService $service) {}

    public function index(): View
    {
        abort_if(!feature('study_content_enabled'), 404);
        abort_unless(auth()->user()->canEditStudyContent(), 403);

        $contents = StudyContent::with(['studyable', 'creator'])->ordered()->get();

        return view('study-contents.index', compact('contents'));
    }

    public function create(): View
    {
        abort_if(!feature('study_content_enabled'), 404);
        abort_unless(auth()->user()->canEditStudyContent(), 403);

        $categories    = Category::orderBy('name')->get();
        $modules       = DrivingModule::ordered()->get();

        return view('study-contents.create', compact('categories', 'modules'));
    }

    public function store(StoreStudyContentRequest $request): RedirectResponse
    {
        abort_if(!feature('study_content_enabled'), 404);
        abort_unless(auth()->user()->canEditStudyContent(), 403);

        $this->service->create($request->validated(), auth()->user());

        return redirect()
            ->route('study-contents.index')
            ->with('success', __('flash.study_content_created'));
    }

    public function edit(StudyContent $studyContent): View
    {
        abort_if(!feature('study_content_enabled'), 404);
        abort_unless(auth()->user()->canEditStudyContent($studyContent), 403);

        $categories = Category::orderBy('name')->get();
        $modules    = DrivingModule::ordered()->get();

        return view('study-contents.edit', compact('studyContent', 'categories', 'modules'));
    }

    public function update(UpdateStudyContentRequest $request, StudyContent $studyContent): RedirectResponse
    {
        abort_if(!feature('study_content_enabled'), 404);
        abort_unless(auth()->user()->canEditStudyContent($studyContent), 403);

        $this->service->update($studyContent, $request->validated(), auth()->user());

        return redirect()
            ->route('study-contents.index')
            ->with('success', __('flash.study_content_updated'));
    }

    public function destroy(StudyContent $studyContent): RedirectResponse
    {
        abort_if(!feature('study_content_enabled'), 404);
        abort_unless(auth()->user()->canEditStudyContent($studyContent), 403);

        $this->service->delete($studyContent);

        return redirect()
            ->route('study-contents.index')
            ->with('success', __('flash.study_content_deleted'));
    }
}
