@extends('layouts.admin')

@section('page-title', $module->name)
@section('content_header')@endsection

@section('content')
<div class="sg-wrapper-fluid">

    <div class="sg-header sg-flex-between mb-3">
        <div>
            <h1 class="sg-header-title">
                <i class="fas fa-car mr-2"></i>
                <span class="sg-badge sg-badge-secondary mr-1">{{ $module->code }}</span>
                {{ $module->name }}
            </h1>
            @if($module->description)
                <p class="text-muted mt-1">{{ $module->description }}</p>
            @endif
        </div>
        <div>
            <a href="{{ route('admin.driving-modules.index') }}" class="sg-btn sg-btn-secondary sg-btn-sm">
                <i class="fas fa-arrow-left mr-1"></i> {{ __('common.back') }}
            </a>
            @if(auth()->user()->canManageDrivingModules())
                <a href="{{ route('admin.driving-modules.edit', $module) }}"
                   class="sg-btn sg-btn-info sg-btn-sm ml-1">
                    <i class="fas fa-edit mr-1"></i> {{ __('common.edit') }}
                </a>
            @endif
        </div>
    </div>

    {{-- Contenuti formativi --}}
    <div class="sg-card mb-4">
        <div class="sg-card-header d-flex justify-content-between align-items-center">
            <h3 class="sg-card-title">{{ __('study_content.section_title') }}</h3>
            @if(auth()->user()->canEditStudyContent())
                <a href="{{ route('study-contents.create') }}?studyable_type={{ urlencode(\App\Models\DrivingModule::class) }}&studyable_id={{ $module->id }}"
                   class="sg-btn sg-btn-primary sg-btn-sm">
                    <i class="fas fa-plus mr-1"></i> {{ __('study_content.btn_new') }}
                </a>
            @endif
        </div>
        <div class="card-body">
            <livewire:study-content-viewer
                :studyable-type="\App\Models\DrivingModule::class"
                :studyable-id="$module->id" />
        </div>
    </div>

    {{-- Sessioni registrate --}}
    <div class="sg-card">
        <div class="sg-card-header">
            <h3 class="sg-card-title">{{ __('driving.title_sessions') }}</h3>
        </div>
        <div class="card-body p-0">
            @if($module->drivingSessions->isEmpty())
                <div class="text-center py-4">
                    <i class="fas fa-clock fa-3x text-muted mb-3 d-block"></i>
                    <p class="text-muted">{{ __('driving.session_none') }}</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="sg-table">
                        <thead>
                            <tr>
                                <th>{{ __('driving.session_date') }}</th>
                                <th>{{ __('driving.session_duration') }}</th>
                                <th>{{ __('driving.session_notes') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($module->drivingSessions as $session)
                                <tr>
                                    <td>{{ $session->conducted_at->format('d/m/Y') }}</td>
                                    <td>{{ $session->duration }} min</td>
                                    <td>{{ $session->notes ?? '—' }}</td>
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
