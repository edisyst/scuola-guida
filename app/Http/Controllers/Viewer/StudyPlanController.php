<?php

namespace App\Http\Controllers\Viewer;

use App\Http\Controllers\Controller;
use App\Services\DiagnosticService;
use App\Services\StudyPlanService;
use Illuminate\View\View;

class StudyPlanController extends Controller
{
    public function show(StudyPlanService $planService, DiagnosticService $diagnosticService): View
    {
        abort_unless(auth()->user()->isViewer(), 403);

        $user = auth()->user();

        return view('study-plan.show', [
            'plan'          => $planService->buildPlan($user),
            'hasDiagnostic' => $diagnosticService->hasDiagnostic($user),
        ]);
    }

    public function startDiagnostic(): View
    {
        abort_unless(auth()->user()->isViewer(), 403);

        return view('diagnostic.show');
    }
}
