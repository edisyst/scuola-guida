@extends('layouts.admin')

@section('title', 'Audit log')
@section('content_header')@endsection

@section('content')
<div class="sg-wrapper">

    <div class="sg-header">
        <p class="sg-header-subtitle">Tracciabilità</p>
        <h1 class="sg-header-title"><i class="fas fa-shield-halved mr-2"></i> Audit log</h1>
    </div>

    <div class="sg-card">
        <div class="table-responsive">
            <table class="sg-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Utente</th>
                        <th>Evento</th>
                        <th>Modello</th>
                        <th>Old</th>
                        <th>New</th>
                        <th>Data</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td class="sg-text-muted">{{ $log->id }}</td>
                        <td>{{ $log->user?->name ?? 'System' }}</td>
                        <td>
                            <span class="sg-badge sg-badge-info">{{ $log->event }}</span>
                        </td>
                        <td>{{ class_basename($log->model_type) }} #{{ $log->model_id }}</td>
                        <td><pre style="margin:0;font-size:.75rem;max-width:280px;overflow:auto;background:var(--sg-bg-soft);padding:8px;border-radius:6px;">{{ json_encode($log->old_values, JSON_PRETTY_PRINT) }}</pre></td>
                        <td><pre style="margin:0;font-size:.75rem;max-width:280px;overflow:auto;background:var(--sg-bg-soft);padding:8px;border-radius:6px;">{{ json_encode($log->new_values, JSON_PRETTY_PRINT) }}</pre></td>
                        <td class="sg-text-muted">{{ $log->created_at }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="sg-table-empty">Nessun evento registrato.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="sg-card-section">
            {{ $logs->links() }}
        </div>
    </div>
</div>
@endsection
