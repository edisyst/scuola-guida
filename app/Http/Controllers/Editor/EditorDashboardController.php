<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use App\Models\LicenseType;
use App\Models\User;
use App\Services\EditorMetricsService;
use App\Services\LicenseTypeService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class EditorDashboardController extends Controller
{
    public function __construct(
        private EditorMetricsService $metrics,
        private LicenseTypeService $licenseTypeService,
    ) {}

    public function index(Request $request): View
    {
        abort_unless(
            auth()->user()->isEditor() || auth()->user()->isAdmin(),
            403
        );

        [$from, $to] = $this->parsePeriod($request);

        $editors         = null;
        $selectedEditorId = null;
        $targetEditor    = auth()->user();

        if (auth()->user()->isAdmin()) {
            $editors = User::where('role', User::ROLE_EDITOR)
                ->orderBy('name')
                ->get(['id', 'name']);

            $selectedEditorId = $request->integer('editor_id') ?: null;

            if ($selectedEditorId && ($found = $editors->find($selectedEditorId))) {
                $targetEditor = $found;
            } else {
                // Nessun editor selezionato → vista aggregata di tutti gli editor
                $targetEditor = null;
            }
        }

        // Filtro tipo di patente (session-based per questa visita)
        $licenseTypeId = $request->has('license_type_id')
            ? $request->query('license_type_id')
            : session('editor_license_type_id');

        if ($request->has('license_type_id')) {
            session(['editor_license_type_id' => $licenseTypeId]);
        }

        $licenseType = $licenseTypeId
            ? LicenseType::find($licenseTypeId)
            : null;

        $licenseTypes = $this->licenseTypeService->allForSelect();

        $productionMetrics = $this->metrics->getProductionMetrics($targetEditor, $from, $to, $licenseType);

        return view('editor.dashboard', [
            'editor'           => $targetEditor,
            'editors'          => $editors,
            'selectedEditorId' => $selectedEditorId,
            'licenseTypes'     => $licenseTypes,
            'selectedLicenseTypeId' => $licenseType?->id,
            'productionMetrics' => $productionMetrics,
            'from'             => $from,
            'to'               => $to,
        ]);
    }

    private function parsePeriod(Request $request): array
    {
        if ($request->filled('from') && $request->filled('to')) {
            return [
                Carbon::parse($request->input('from'))->startOfDay(),
                Carbon::parse($request->input('to'))->endOfDay(),
            ];
        }

        $preset = $request->input('period', 'month');

        return match ($preset) {
            'quarter' => [now()->firstOfQuarter()->startOfDay(), now()->endOfDay()],
            'year'    => [now()->startOfYear()->startOfDay(), now()->endOfDay()],
            default   => [now()->startOfMonth()->startOfDay(), now()->endOfDay()],
        };
    }
}
