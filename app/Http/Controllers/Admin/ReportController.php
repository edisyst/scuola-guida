<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReportFilterRequest;
use App\Services\ReportingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(private ReportingService $reporting) {}

    public function index(): View
    {
        abort_unless(auth()->user()->canEditQuiz(), 403);

        return view('admin.reports.index', [
            'defaultFrom' => now()->startOfMonth()->format('Y-m-d'),
            'defaultTo'   => now()->endOfMonth()->format('Y-m-d'),
        ]);
    }

    public function show(ReportFilterRequest $request): View
    {
        abort_unless(auth()->user()->canEditQuiz(), 403);

        [$from, $to] = $this->parseDates($request);
        $compare = $request->boolean('compare');

        if ($compare) {
            $data = $this->reporting->buildComparisonReport($from, $to);
        } else {
            $data = ['current' => $this->reporting->buildPeriodReport($from, $to)];
        }

        return view('admin.reports.show', array_merge($data, [
            'from'    => $from,
            'to'      => $to,
            'compare' => $compare,
            'filters' => $request->validated(),
        ]));
    }

    public function exportPdf(ReportFilterRequest $request): Response
    {
        abort_unless(auth()->user()->canEditQuiz(), 403);

        [$from, $to] = $this->parseDates($request);
        $compare = $request->boolean('compare');

        if ($compare) {
            $data = $this->reporting->buildComparisonReport($from, $to);
        } else {
            $data = ['current' => $this->reporting->buildPeriodReport($from, $to)];
        }

        $pdf = Pdf::loadView('admin.reports.pdf.period', array_merge($data, [
            'from'         => $from,
            'to'           => $to,
            'compare'      => $compare,
            'generated_at' => now(),
        ]))->setPaper('a4', 'portrait');

        $filename = "report-{$from->format('Y-m-d')}-{$to->format('Y-m-d')}.pdf";

        return $pdf->download($filename);
    }

    private function parseDates(ReportFilterRequest $request): array
    {
        $v = $request->validated();

        return [
            Carbon::parse($v['from'])->startOfDay(),
            Carbon::parse($v['to'])->endOfDay(),
        ];
    }
}
