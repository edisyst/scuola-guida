@extends('layouts.admin')

@section('title', __('enrollments.title'))
@section('content_header')@endsection

@section('content')
<div class="sg-wrapper">

    <div class="sg-header">
        <p class="sg-header-subtitle">{{ __('enrollments.subtitle') }}</p>
        <h1 class="sg-header-title"><i class="fas fa-list-check mr-2"></i> {{ __('enrollments.title') }}</h1>
    </div>

    <div class="sg-card">
        @if($enrollments->isEmpty())
            <div class="sg-table-empty">{{ __('enrollments.no_enrollments') }}</div>
        @else
            <div class="table-responsive">
                <table class="sg-table">
                    <thead>
                        <tr>
                            <th>{{ __('enrollments.col_quiz') }}</th>
                            <th>{{ __('enrollments.col_status') }}</th>
                            <th>{{ __('enrollments.col_requested') }}</th>
                            <th>{{ __('enrollments.col_reviewed') }}</th>
                            <th class="text-right"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($enrollments as $enrollment)
                            <tr>
                                <td><strong>{{ $enrollment->quiz->title ?? '—' }}</strong></td>
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
                                <td class="sg-text-muted">{{ $enrollment->created_at->format('d/m/Y H:i') }}</td>
                                <td class="sg-text-muted">
                                    @if($enrollment->reviewed_at)
                                        {{ $enrollment->reviewed_at->format('d/m/Y H:i') }}
                                        <br><small>{{ $enrollment->reviewer->name ?? '' }}</small>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="text-right">
                                    @if($enrollment->isApproved())
                                        <a href="{{ route('quiz.play', $enrollment->quiz) }}"
                                           class="sg-btn sg-btn-primary sg-btn-sm"
                                           onclick="return confirm('{{ __('enrollments.play_confirm') }}');">
                                            <i class="fas fa-play"></i> {{ __('enrollments.play_btn') }}
                                        </a>
                                    @endif
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
