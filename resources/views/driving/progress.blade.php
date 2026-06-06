@extends('layouts.admin')

@section('page-title', __('driving.title_progress'))

@section('content_header')@endsection

@section('content')
<div class="sg-wrapper">

    {{-- Intestazione pagina --}}
    <div class="sg-header sg-flex-between mb-3">
        <div>
            <h1 class="sg-header-title">
                <i class="fas fa-car mr-2"></i> {{ __('driving.title_progress') }}
            </h1>
            @if($licenseType)
                <p class="sg-header-subtitle sg-mt-1">{{ $licenseType->name }}</p>
            @endif
        </div>
    </div>

    {{-- Stato vuoto: nessun tipo patente o nessun modulo --}}
    @if(!$licenseType || empty($progress['modules']))
        <div class="text-center py-5">
            <i class="fas fa-car fa-3x text-muted mb-3 d-block"></i>
            <p class="text-muted">{{ __('driving.progress_empty') }}</p>
        </div>
    @else

        {{-- Widget KPI: completamento globale e ore totali --}}
        <div class="row">
            <div class="col-md-4">
                <div class="small-box bg-{{ $progress['all_completed'] ? 'success' : 'info' }}">
                    <div class="inner">
                        <h3>{{ $progress['percentage'] }}%</h3>
                        <p>{{ __('driving.progress_title') }}</p>
                    </div>
                    <div class="icon"><i class="fas fa-car"></i></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3>{{ $progress['total_completed'] }} h</h3>
                        <p>{{ __('driving.session_duration') }}</p>
                    </div>
                    <div class="icon"><i class="fas fa-clock"></i></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="small-box bg-{{ $progress['all_completed'] ? 'success' : 'warning' }}">
                    <div class="inner">
                        <h3>{{ $progress['total_required'] }} h</h3>
                        <p>{{ __('driving.col_required_hours') }}</p>
                    </div>
                    <div class="icon"><i class="fas fa-flag-checkered"></i></div>
                </div>
            </div>
        </div>

        {{-- Banner completamento totale --}}
        @if($progress['all_completed'])
            <div class="alert alert-success">
                <i class="fas fa-check-circle mr-1"></i>
                {{ __('driving.progress_all_done') }}
            </div>

            {{-- Pulsante download riepilogo PDF --}}
            <div class="mb-3">
                <a href="{{ route('driving.attestation.download', auth()->user()) }}"
                   class="sg-btn sg-btn-primary"
                   target="_blank">
                    <i class="fas fa-file-pdf mr-1"></i> {{ __('driving.download_attestation') }}
                </a>
            </div>
        @else
            <div class="alert alert-info">
                <i class="fas fa-info-circle mr-1"></i>
                {{ __('driving.download_attestation_pending') }}
            </div>
        @endif

        {{-- Dettaglio per modulo --}}
        <div class="sg-card">
            <div class="sg-card-header">
                <h3 class="sg-card-title">{{ __('driving.title_modules') }}</h3>
            </div>
            <div class="p-3">
                @foreach($progress['modules'] as $item)
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <div>
                                <strong>{{ $item['module']->code }} — {{ $item['module']->name }}</strong>
                                <small class="text-muted ml-2">
                                    {{ $item['sessions_count'] }} {{ __('driving.progress_sessions') }}
                                </small>
                            </div>
                            <div>
                                <span class="{{ $item['completed'] ? 'text-success' : 'text-muted' }}">
                                    {{ $item['completed_hours'] }} / {{ $item['required_hours'] }} h
                                </span>
                                @if($item['completed'])
                                    <span class="sg-badge sg-badge-success ml-1">
                                        {{ __('driving.progress_completed') }}
                                    </span>
                                @endif
                            </div>
                        </div>
                        @php
                            $pct = $item['required_hours'] > 0
                                ? min(100, round(($item['completed_hours'] / $item['required_hours']) * 100))
                                : 0;
                        @endphp
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar {{ $item['completed'] ? 'bg-success' : 'bg-primary' }}"
                                 style="width: {{ $pct }}%"
                                 aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100">
                            </div>
                        </div>
                        @if($item['last_session_date'])
                            <small class="text-muted">
                                Ultima sessione: {{ $item['last_session_date']->format('d/m/Y') }}
                            </small>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

    @endif

</div>
@endsection
