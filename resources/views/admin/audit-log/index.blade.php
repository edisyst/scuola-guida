@extends('adminlte::page')

@section('title', 'Audit log')

@section('content_header')
    <h1><i class="fas fa-history mr-2"></i>Audit log</h1>
@endsection

@section('content')
<div class="container-fluid">

    {{-- ── FILTRI ──────────────────────────────────────────────────────────── --}}
    <div class="card card-default collapsed-card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-filter mr-1"></i>Filtri</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                    <i class="fas fa-plus"></i>
                </button>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.audit.index') }}" id="filter-form">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Utente</label>
                            <select name="user_id" class="form-control form-control-sm">
                                <option value="">— Tutti gli utenti —</option>
                                @foreach($users as $u)
                                    <option value="{{ $u->id }}" @selected(request('user_id') == $u->id)>
                                        {{ $u->name }} ({{ $u->email }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Tipo oggetto</label>
                            <select name="auditable_type" class="form-control form-control-sm">
                                <option value="">— Tutti i tipi —</option>
                                @foreach($auditableTypes as $class => $label)
                                    <option value="{{ $class }}" @selected(request('auditable_type') === $class)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Azione</label>
                            <select name="event" class="form-control form-control-sm">
                                <option value="">— Tutte le azioni —</option>
                                <option value="created"  @selected(request('event') === 'created')>Creazione</option>
                                <option value="updated"  @selected(request('event') === 'updated')>Modifica</option>
                                <option value="deleted"  @selected(request('event') === 'deleted')>Eliminazione</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Dal</label>
                            <input type="date" name="from" class="form-control form-control-sm"
                                   value="{{ request('from') }}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Al</label>
                            <input type="date" name="to" class="form-control form-control-sm"
                                   value="{{ request('to') }}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Ricerca testo</label>
                            <input type="text" name="search" class="form-control form-control-sm"
                                   placeholder="Cerca nei valori…"
                                   value="{{ request('search') }}" maxlength="255">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="fas fa-search mr-1"></i>Filtra
                        </button>
                        <a href="{{ route('admin.audit.index') }}" class="btn btn-sm btn-secondary">
                            <i class="fas fa-times mr-1"></i>Reset filtri
                        </a>
                        <a href="{{ route('admin.audit.export') . '?' . http_build_query(request()->except('page')) }}"
                           class="btn btn-sm btn-success ml-auto">
                            <i class="fas fa-file-excel mr-1"></i>Esporta Excel
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- ── TABELLA RISULTATI ────────────────────────────────────────────────── --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-list mr-1"></i>
                {{ number_format($logs->total()) }} eventi
            </h3>
        </div>
        <div class="card-body p-0">
            @if($logs->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-clipboard-list fa-3x mb-3 d-block"></i>
                    <p class="mb-0">Nessun evento trovato con i filtri attivi.</p>
                    @if(request()->hasAny(['user_id','auditable_type','event','from','to','search']))
                        <a href="{{ route('admin.audit.index') }}" class="btn btn-sm btn-outline-secondary mt-2">
                            Rimuovi filtri
                        </a>
                    @endif
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th style="width:145px">Data</th>
                                <th>Utente</th>
                                <th style="width:110px">Azione</th>
                                <th>Tipo oggetto</th>
                                <th class="d-none d-md-table-cell">Riepilogo modifiche</th>
                                <th style="width:90px"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($logs as $log)
                                @php
                                    $service = app(\App\Services\AuditLogService::class);
                                    $userLabel = $service->formatUser($log);
                                    $typeLabel = $service->typeLabel($log->model_type);
                                    $summary   = $service->diffSummary($log);
                                @endphp
                                <tr>
                                    <td>
                                        <span title="{{ $log->created_at->format('d/m/Y H:i:s') }}">
                                            {{ $log->created_at->format('d/m/Y H:i') }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($log->user_id === null)
                                            <span class="text-muted"><i class="fas fa-cog mr-1"></i>Sistema</span>
                                        @elseif(str_ends_with((string) $log->user?->email, '@eliminato.invalid'))
                                            <span class="text-muted"><i class="fas fa-user-slash mr-1"></i>Utente anonimizzato</span>
                                        @else
                                            <span>{{ $log->user?->name ?? "Utente #$log->user_id" }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($log->event === 'created')
                                            <span class="badge badge-success">Creazione</span>
                                        @elseif($log->event === 'updated')
                                            <span class="badge badge-warning">Modifica</span>
                                        @else
                                            <span class="badge badge-danger">Eliminazione</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $typeLabel }} <small class="text-muted">#{{ $log->model_id }}</small>
                                    </td>
                                    <td class="d-none d-md-table-cell text-muted small">
                                        {{ $summary }}
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.audit.show', $log) }}"
                                           class="btn btn-xs btn-outline-secondary">
                                            <i class="fas fa-eye mr-1"></i>Dettaglio
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-3 py-2">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>
    </div>

</div>
@endsection
