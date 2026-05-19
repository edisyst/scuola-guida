<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuestionReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuestionReportController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(auth()->user()->canEditQuestion(), 403);

        $query = QuestionReport::with(['question:id,question,category_id', 'user:id,name,email', 'resolvedBy:id,name'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('question_id')) {
            $query->where('question_id', $request->integer('question_id'));
        }

        $reports = $query->paginate(20)->withQueryString();

        $types = QuestionReport::types();

        $stats = [
            'pending'  => QuestionReport::pending()->count(),
            'accepted' => QuestionReport::accepted()->count(),
            'rejected' => QuestionReport::rejected()->count(),
        ];

        return view('admin.question-reports.index', compact('reports', 'types', 'stats'));
    }

    public function show(QuestionReport $report): View
    {
        abort_unless(auth()->user()->canEditQuestion(), 403);

        $report->load(['question.category', 'user', 'resolvedBy']);
        $types = QuestionReport::types();

        return view('admin.question-reports.show', compact('report', 'types'));
    }

    public function accept(Request $request, QuestionReport $report): RedirectResponse
    {
        abort_unless(auth()->user()->canEditQuestion(), 403);

        $data = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $report->update([
            'status'      => QuestionReport::STATUS_ACCEPTED,
            'admin_note'  => $data['admin_note'] ?? null,
            'resolved_by' => auth()->id(),
            'resolved_at' => now(),
        ]);

        return redirect()
            ->route('admin.question-reports.index')
            ->with('success', 'Segnalazione accettata.');
    }

    public function reject(Request $request, QuestionReport $report): RedirectResponse
    {
        abort_unless(auth()->user()->canEditQuestion(), 403);

        $data = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $report->update([
            'status'      => QuestionReport::STATUS_REJECTED,
            'admin_note'  => $data['admin_note'] ?? null,
            'resolved_by' => auth()->id(),
            'resolved_at' => now(),
        ]);

        return redirect()
            ->route('admin.question-reports.index')
            ->with('success', 'Segnalazione rifiutata.');
    }

    public function destroy(QuestionReport $report): RedirectResponse
    {
        abort_unless(auth()->user()->canEditQuestion(), 403);

        $report->delete();

        return redirect()
            ->route('admin.question-reports.index')
            ->with('success', 'Segnalazione eliminata.');
    }
}
