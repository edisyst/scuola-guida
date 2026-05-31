@extends('adminlte::page')

@section('title', 'Dettaglio evento audit')

@section('content_header')
    <h1><i class="fas fa-history mr-2"></i>Dettaglio evento audit</h1>
@endsection

@section('content')
<div class="container-fluid">

    <div class="mb-3">
        <a href="{{ url()->previous(route('admin.audit.index')) }}" class="btn btn-sm btn-secondary">
            <i class="fas fa-arrow-left mr-1"></i>Torna all'elenco
        </a>
    </div>

    {{-- ── METADATA ─────────────────────────────────────────────────────────── --}}
    <div class="card card-default">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-info-circle mr-1"></i>Informazioni evento</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <dl class="mb-0">
                        <dt class="text-muted small">Data e ora</dt>
                        <dd>{{ $log->created_at->format('d/m/Y H:i:s') }}</dd>
                    </dl>
                </div>
                <div class="col-md-3">
                    <dl class="mb-0">
                        <dt class="text-muted small">Utente</dt>
                        <dd>
                            @if($log->user_id === null)
                                <span class="text-muted"><i class="fas fa-cog mr-1"></i>Sistema</span>
                            @elseif(str_ends_with((string) $log->user?->email, '@eliminato.invalid'))
                                <span class="text-muted"><i class="fas fa-user-slash mr-1"></i>Utente anonimizzato</span>
                            @else
                                {{ $log->user?->name ?? "Utente #$log->user_id" }}
                                @if($log->user?->email)
                                    <br><small class="text-muted">{{ $log->user->email }}</small>
                                @endif
                            @endif
                        </dd>
                    </dl>
                </div>
                <div class="col-md-3">
                    <dl class="mb-0">
                        <dt class="text-muted small">Azione</dt>
                        <dd>
                            @if($log->event === 'created')
                                <span class="badge badge-success">Creazione</span>
                            @elseif($log->event === 'updated')
                                <span class="badge badge-warning">Modifica</span>
                            @else
                                <span class="badge badge-danger">Eliminazione</span>
                            @endif
                        </dd>
                    </dl>
                </div>
                <div class="col-md-3">
                    <dl class="mb-0">
                        <dt class="text-muted small">Oggetto modificato</dt>
                        <dd>
                            @php
                                $typeLabel = app(\App\Services\AuditLogService::class)->typeLabel($log->model_type);
                            @endphp
                            {{ $typeLabel }} <span class="text-muted">#{{ $log->model_id }}</span>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    {{-- ── DIFF ─────────────────────────────────────────────────────────────── --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-exchange-alt mr-1"></i>
                @if($log->event === 'created')
                    Valori al momento della creazione
                @elseif($log->event === 'deleted')
                    Valori al momento dell'eliminazione
                @else
                    Campi modificati
                @endif
            </h3>
        </div>
        <div class="card-body p-0">
            @if(empty($diff))
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-check-circle fa-2x mb-2 d-block"></i>
                    <p class="mb-0">Nessun campo da mostrare.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th style="width:200px">Campo</th>
                                @if($log->event !== 'created')
                                    <th>Prima</th>
                                @endif
                                @if($log->event !== 'deleted')
                                    <th>Dopo</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($diff as $item)
                                <tr>
                                    <td><strong>{{ $item['label'] }}</strong><br>
                                        <small class="text-muted">{{ $item['field'] }}</small>
                                    </td>
                                    @if($log->event !== 'created')
                                        <td class="align-top">
                                            @if($item['old'] === null)
                                                <span class="text-muted">—</span>
                                            @elseif(is_array($item['old']))
                                                <pre class="mb-0 small" style="white-space:pre-wrap">{{ json_encode($item['old'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                            @else
                                                <span class="{{ $log->event === 'updated' ? 'text-danger' : '' }}">{{ $item['old'] }}</span>
                                            @endif
                                        </td>
                                    @endif
                                    @if($log->event !== 'deleted')
                                        <td class="align-top">
                                            @if($item['new'] === null)
                                                <span class="text-muted">—</span>
                                            @elseif(is_array($item['new']))
                                                <pre class="mb-0 small" style="white-space:pre-wrap">{{ json_encode($item['new'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                            @else
                                                <span class="{{ $log->event === 'updated' ? 'text-success' : '' }}">{{ $item['new'] }}</span>
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

</div>
@endsection
