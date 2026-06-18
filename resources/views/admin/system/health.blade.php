@extends('layouts.admin')

@section('title', __('system.service_health_title'))

@section('content_header')@endsection

@section('content')
    <div class="sg-wrapper">

        <div class="sg-header sg-flex-between">
            <h1 class="sg-header-title">{{ __('system.service_health_title') }}</h1>
            <div class="sg-header-actions">
                <a href="{{ route('admin.system.health') }}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-sync-alt mr-1"></i>{{ __('system.refresh') }}
                </a>
            </div>
        </div>

        <div class="row">

            {{-- Database --}}
            <div class="col-md-4 col-sm-6">
                <div class="card">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div>
                            <i class="fas fa-database fa-2x text-muted mr-3"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="font-weight-bold">{{ __('system.service_database') }}</div>
                            @if(isset($checks['database']['detail']))
                                <small class="text-muted">{{ $checks['database']['detail'] }}</small>
                            @endif
                        </div>
                        <div>
                            @if($checks['database']['status'] === 'ok')
                                <span class="badge badge-success">{{ $checks['database']['label'] }}</span>
                            @else
                                <span class="badge badge-danger">{{ $checks['database']['label'] }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Redis --}}
            <div class="col-md-4 col-sm-6">
                <div class="card">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div>
                            <i class="fas fa-memory fa-2x text-muted mr-3"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="font-weight-bold">{{ __('system.service_redis') }}</div>
                            @if(isset($checks['redis']['detail']))
                                <small class="text-muted">{{ $checks['redis']['detail'] }}</small>
                            @endif
                        </div>
                        <div>
                            @if($checks['redis']['status'] === 'ok')
                                <span class="badge badge-success">{{ $checks['redis']['label'] }}</span>
                            @else
                                <span class="badge badge-danger">{{ $checks['redis']['label'] }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Queue --}}
            <div class="col-md-4 col-sm-6">
                <div class="card">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div>
                            <i class="fas fa-tasks fa-2x text-muted mr-3"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="font-weight-bold">{{ __('system.service_queue') }}</div>
                            @if(isset($checks['queue']['pending']))
                                <small class="text-muted">
                                    {{ __('system.pending_jobs') }}: {{ $checks['queue']['pending'] }}
                                    &mdash; {{ __('system.failed_jobs') }}: {{ $checks['queue']['failed'] }}
                                </small>
                            @elseif(isset($checks['queue']['detail']))
                                <small class="text-muted">{{ $checks['queue']['detail'] }}</small>
                            @endif
                        </div>
                        <div>
                            @if($checks['queue']['status'] === 'ok')
                                <span class="badge badge-success">{{ $checks['queue']['label'] }}</span>
                            @elseif($checks['queue']['status'] === 'warning')
                                <span class="badge badge-warning">{{ $checks['queue']['label'] }}</span>
                            @else
                                <span class="badge badge-danger">{{ $checks['queue']['label'] }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Storage --}}
            <div class="col-md-4 col-sm-6">
                <div class="card">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div>
                            <i class="fas fa-hdd fa-2x text-muted mr-3"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="font-weight-bold">{{ __('system.service_storage') }}</div>
                            @if(isset($checks['storage']['detail']))
                                <small class="text-muted">{{ $checks['storage']['detail'] }}</small>
                            @endif
                        </div>
                        <div>
                            @if($checks['storage']['status'] === 'ok')
                                <span class="badge badge-success">{{ $checks['storage']['label'] }}</span>
                            @else
                                <span class="badge badge-danger">{{ $checks['storage']['label'] }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Mail --}}
            <div class="col-md-4 col-sm-6">
                <div class="card">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div>
                            <i class="fas fa-envelope fa-2x text-muted mr-3"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="font-weight-bold">{{ __('system.service_mail') }}</div>
                            @if(isset($checks['mail']['host']))
                                <small class="text-muted">{{ $checks['mail']['driver'] }}: {{ $checks['mail']['host'] }}</small>
                            @elseif(isset($checks['mail']['driver']))
                                <small class="text-muted">{{ $checks['mail']['driver'] }}</small>
                            @endif
                        </div>
                        <div>
                            @if($checks['mail']['status'] === 'ok')
                                <span class="badge badge-success">{{ $checks['mail']['label'] }}</span>
                            @elseif($checks['mail']['status'] === 'warning')
                                <span class="badge badge-warning">{{ $checks['mail']['label'] }}</span>
                            @else
                                <span class="badge badge-danger">{{ $checks['mail']['label'] }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Twilio --}}
            <div class="col-md-4 col-sm-6">
                <div class="card">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div>
                            <i class="fas fa-sms fa-2x text-muted mr-3"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="font-weight-bold">{{ __('system.service_twilio') }}</div>
                        </div>
                        <div>
                            @if($checks['twilio']['status'] === 'ok')
                                <span class="badge badge-success">{{ $checks['twilio']['label'] }}</span>
                            @elseif($checks['twilio']['status'] === 'warning')
                                <span class="badge badge-warning">{{ $checks['twilio']['label'] }}</span>
                            @else
                                <span class="badge badge-danger">{{ $checks['twilio']['label'] }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
@stop
