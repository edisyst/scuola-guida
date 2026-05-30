<?php

namespace App\Http\Controllers\Admin;

use App\Exports\AuditLogExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\AuditLogFilterRequest;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditLogService;
use Maatwebsite\Excel\Facades\Excel;

class AuditLogController extends Controller
{
    public function __construct(private AuditLogService $service) {}

    public function index(AuditLogFilterRequest $request)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $logs = $this->service
            ->query($request->validated())
            ->paginate(50)
            ->withQueryString();

        $users          = User::orderBy('name')->get(['id', 'name', 'email']);
        $auditableTypes = $this->service->getAuditableTypes();

        return view('admin.audit-log.index', compact('logs', 'users', 'auditableTypes'));
    }

    public function show(AuditLog $log)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $log->load('user');
        $diff = $this->service->getDiff($log);

        return view('admin.audit-log.show', compact('log', 'diff'));
    }

    public function export(AuditLogFilterRequest $request)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $builder  = $this->service->query($request->validated());
        $filename = 'audit-log-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(new AuditLogExport($builder), $filename);
    }
}
