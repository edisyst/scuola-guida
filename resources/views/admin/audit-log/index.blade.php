@extends('layouts.admin')

@section('title', __('audit.title'))

@section('content_header')@endsection

@section('content')
<div class="sg-wrapper-fluid">

    <div class="sg-header">
        <h1 class="sg-header-title"><i class="fas fa-history mr-2"></i>{{ __('audit.title') }}</h1>
        <p class="sg-header-subtitle sg-mt-1">Traccia delle operazioni effettuate dagli utenti sulle entità del sistema.</p>
    </div>

    {{-- ── FILTRI ──────────────────────────────────────────────────────────── --}}
    <div class="card card-default collapsed-card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-filter mr-1"></i>{{ __('audit.filters_title') }}</h3>
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
                            <label>{{ __('audit.filter_user') }}</label>
                            <select name="user_id" class="form-control form-control-sm">
                                <option value="">{{ __('audit.filter_user_all') }}</option>
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
                            <label>{{ __('audit.filter_type') }}</label>
                            <select name="auditable_type" class="form-control form-control-sm">
                                <option value="">{{ __('audit.filter_type_all') }}</option>
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
                            <label>{{ __('audit.filter_action') }}</label>
                            <select name="event" class="form-control form-control-sm">
                                <option value="">{{ __('audit.filter_action_all') }}</option>
                                <option value="created"  @selected(request('event') === 'created')>{{ __('audit.event_created') }}</option>
                                <option value="updated"  @selected(request('event') === 'updated')>{{ __('audit.event_updated') }}</option>
                                <option value="deleted"  @selected(request('event') === 'deleted')>{{ __('audit.event_deleted') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>{{ __('audit.filter_from') }}</label>
                            <input type="date" name="from" class="form-control form-control-sm"
                                   value="{{ request('from') }}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>{{ __('audit.filter_to') }}</label>
                            <input type="date" name="to" class="form-control form-control-sm"
                                   value="{{ request('to') }}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>{{ __('audit.filter_search') }}</label>
                            <input type="text" name="search" class="form-control form-control-sm"
                                   placeholder="{{ __('audit.filter_search_ph') }}"
                                   value="{{ request('search') }}" maxlength="255">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="fas fa-search mr-1"></i>{{ __('audit.filter_submit') }}
                        </button>
                        <a href="{{ route('admin.audit.index') }}" class="btn btn-sm btn-secondary">
                            <i class="fas fa-times mr-1"></i>{{ __('audit.filter_reset') }}
                        </a>
                        <a href="{{ route('admin.audit.export') . '?' . http_build_query(request()->except('page')) }}"
                           class="btn btn-sm btn-success ml-auto">
                            <i class="fas fa-file-excel mr-1"></i>{{ __('audit.export_excel') }}
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
                {{ __('audit.events_count', ['count' => number_format($logs->total())]) }}
            </h3>
        </div>
        <div class="card-body p-0">
            @if($logs->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-clipboard-list fa-3x mb-3 d-block"></i>
                    <p class="mb-0">{{ __('audit.no_events') }}</p>
                    @if(request()->hasAny(['user_id','auditable_type','event','from','to','search']))
                        <a href="{{ route('admin.audit.index') }}" class="btn btn-sm btn-outline-secondary mt-2">
                            {{ __('audit.filter_remove') }}
                        </a>
                    @endif
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th style="width:145px">{{ __('audit.col_date') }}</th>
                                <th>{{ __('audit.col_user') }}</th>
                                <th style="width:110px">{{ __('audit.col_action') }}</th>
                                <th>{{ __('audit.col_type') }}</th>
                                <th class="d-none d-md-table-cell">{{ __('audit.col_summary') }}</th>
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
                                            <span class="text-muted"><i class="fas fa-cog mr-1"></i>{{ __('audit.system_user') }}</span>
                                        @elseif(str_ends_with((string) $log->user?->email, '@eliminato.invalid'))
                                            <span class="text-muted"><i class="fas fa-user-slash mr-1"></i>{{ __('audit.anonymous_user') }}</span>
                                        @else
                                            <span>{{ $log->user?->name ?? "Utente #$log->user_id" }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($log->event === 'created')
                                            <span class="badge badge-success">{{ __('audit.event_created') }}</span>
                                        @elseif($log->event === 'updated')
                                            <span class="badge badge-warning">{{ __('audit.event_updated') }}</span>
                                        @else
                                            <span class="badge badge-danger">{{ __('audit.event_deleted') }}</span>
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
                                            <i class="fas fa-eye mr-1"></i>{{ __('audit.col_detail') }}
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
