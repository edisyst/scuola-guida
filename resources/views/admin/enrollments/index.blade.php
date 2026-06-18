@extends('layouts.admin')

@section('title', __('enrollments.admin_title'))
@section('content_header')@endsection

@section('content')
<div class="sg-wrapper-fluid">

    <div class="sg-header sg-flex-between">
        <div>
            <p class="sg-header-subtitle">{{ __('enrollments.admin_subtitle') }}</p>
            <h1 class="sg-header-title"><i class="fas fa-user-check mr-2"></i> {{ __('enrollments.admin_title') }}</h1>
        </div>
        @if($pendingCount > 0)
            <span class="sg-badge sg-badge-warning">
                <i class="fas fa-clock"></i> {{ $pendingCount }} {{ __('enrollments.status_pending') }}
            </span>
        @endif
    </div>

    <div class="sg-card sg-mb-3">
        <div class="sg-card-body sg-flex flex-wrap" style="gap:8px;">
            <a href="{{ route('admin.enrollments.index') }}"
               class="sg-btn sg-btn-sm {{ !$status ? 'sg-btn-primary' : 'sg-btn-light' }}">{{ __('enrollments.filter_all') }}</a>
            @foreach(\App\Models\QuizEnrollment::STATUSES as $key => $label)
                <a href="{{ route('admin.enrollments.index', ['status' => $key, 'license_type_id' => $licenseTypeId]) }}"
                   class="sg-btn sg-btn-sm {{ $status === $key ? 'sg-btn-primary' : 'sg-btn-light' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>

    <div class="sg-card sg-mb-3">
        <div class="sg-card-body">
            <form method="GET" action="{{ route('admin.enrollments.index') }}" class="row align-items-end">
                <input type="hidden" name="status" value="{{ $status }}">
                <div class="col-12 col-md-4">
                    <label class="sg-label mb-2">{{ __('enrollments.filter_license_type') }}</label>
                    <select name="license_type_id" class="sg-form-control">
                        <option value="">{{ __('enrollments.filter_all') }}</option>
                        @foreach($licenseTypes as $lt)
                            <option value="{{ $lt->id }}" @selected($licenseTypeId == $lt->id)>{{ $lt->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <button type="submit" class="sg-btn sg-btn-primary sg-btn-sm" style="width:100%;">
                        <i class="fas fa-filter"></i> {{ __('common.filter') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="sg-card">
        @if($enrollments->isEmpty())
            <div class="sg-table-empty">{{ __('enrollments.no_results') }}</div>
        @else
            <div class="table-responsive">
                <table class="sg-table">
                    <thead>
                        <tr>
                            <th>{{ __('enrollments.col_id') }}</th>
                            <th>{{ __('enrollments.col_quiz') }}</th>
                            <th>{{ __('enrollments.col_user') }}</th>
                            <th>{{ __('enrollments.col_status') }}</th>
                            <th>{{ __('enrollments.col_license_type') }}</th>
                            <th>{{ __('enrollments.col_requested') }}</th>
                            <th>{{ __('enrollments.col_reviewed') }}</th>
                            <th class="text-right">{{ __('enrollments.col_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($enrollments as $enrollment)
                            <tr>
                                <td class="sg-text-muted">{{ $enrollment->id }}</td>
                                <td><strong>{{ $enrollment->quiz->title ?? '—' }}</strong></td>
                                <td>{{ $enrollment->user->name ?? '—' }}</td>
                                <td>
                                    @switch($enrollment->status)
                                        @case(\App\Models\QuizEnrollment::STATUS_PENDING)
                                            <span class="sg-badge sg-badge-warning">{{ __('enrollments.status_pending') }}</span>
                                            @break
                                        @case(\App\Models\QuizEnrollment::STATUS_APPROVED)
                                            <span class="sg-badge sg-badge-success">{{ __('enrollments.status_approved') }}</span>
                                            @break
                                        @case(\App\Models\QuizEnrollment::STATUS_REJECTED)
                                            <span class="sg-badge sg-badge-danger">{{ __('enrollments.status_rejected') }}</span>
                                            @break
                                        @case(\App\Models\QuizEnrollment::STATUS_COMPLETED)
                                            <span class="sg-badge sg-badge-info">{{ __('enrollments.status_completed') }}</span>
                                            @break
                                    @endswitch
                                </td>
                                <td>
                                    @if($enrollment->quiz?->licenseType)
                                        <span class="badge badge-secondary">{{ $enrollment->quiz->licenseType->code }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="sg-text-muted">{{ $enrollment->created_at->format('d/m/Y H:i') }}</td>
                                <td class="sg-text-muted">
                                    {{ $enrollment->reviewer->name ?? '—' }}
                                </td>
                                <td class="text-right">
                                    {{-- bottoni azione: gap-2 separa Approva e Rifiuta --}}
                                    <div class="d-inline-flex gap-2 align-items-center">
                                        @if($enrollment->isPending())
                                            <form method="POST" action="{{ route('admin.enrollments.approve', $enrollment) }}" class="d-inline">
                                                @csrf
                                                <button class="sg-btn sg-btn-success sg-btn-sm">
                                                    <i class="fas fa-check"></i> {{ __('enrollments.action_approve') }}
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.enrollments.reject', $enrollment) }}" class="d-inline">
                                                @csrf
                                                <button class="sg-btn sg-btn-outline sg-btn-sm">
                                                    <i class="fas fa-times"></i> {{ __('enrollments.action_reject') }}
                                                </button>
                                            </form>
                                        @elseif($enrollment->isCompleted() || $enrollment->isRejected())
                                            <form method="POST"
                                                  action="{{ route('admin.enrollments.reopen', ['quiz' => $enrollment->quiz_id, 'user' => $enrollment->user_id]) }}"
                                                  class="d-inline"
                                                  onsubmit="return confirm('{{ __('enrollments.confirm_reopen') }}');">
                                                @csrf
                                                <button class="sg-btn sg-btn-light sg-btn-sm">
                                                    <i class="fas fa-redo"></i> {{ __('enrollments.action_reopen') }}
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="sg-card-section">
                {{ $enrollments->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
