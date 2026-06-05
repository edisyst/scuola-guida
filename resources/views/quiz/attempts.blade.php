@extends('layouts.admin')

@section('title', __('enrollments.attempts_title'))

@section('content_header')@endsection

@section('content')
<div class="sg-wrapper">

    <div class="sg-header sg-flex-between">
        <div>
            <p class="sg-header-subtitle">{{ __('enrollments.attempts_subtitle') }}</p>
            <h1 class="sg-header-title">{{ __('enrollments.attempts_title') }}</h1>
        </div>
        <div class="sg-header-actions">
            {{-- entry point quiz: catalogo dei quiz confermati per iscrizione --}}
            <a href="{{ route('quiz.confirmed.index') }}" class="sg-btn sg-btn-light sg-btn-sm">
                <i class="fas fa-clipboard-list"></i> {{ __('dashboard.start_quiz') }}
            </a>
        </div>
    </div>

    <div class="sg-card">
        @if($attempts->isEmpty())
            <div class="sg-table-empty">
                <p class="sg-mb-2">{{ __('enrollments.no_attempts') }}</p>
                {{-- entry point quiz: catalogo dei quiz confermati per iscrizione --}}
                <a href="{{ route('quiz.confirmed.index') }}" class="sg-btn sg-btn-primary">
                    <i class="fas fa-clipboard-list"></i> {{ __('dashboard.start_quiz') }}
                </a>
            </div>
        @else
            <div class="table-responsive">
                <table class="sg-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('dashboard.col_quiz') }}</th>
                            <th>{{ __('dashboard.col_score') }}</th>
                            <th>{{ __('dashboard.col_pct') }}</th>
                            <th>{{ __('dashboard.col_result') }}</th>
                            <th>{{ __('dashboard.col_duration') }}</th>
                            <th>{{ __('dashboard.col_date') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($attempts as $attempt)
                            <tr>
                                <td class="sg-text-muted">{{ $attempt->id }}</td>
                                <td>{{ $attempt->quiz->title ?? '—' }}</td>
                                <td>
                                    <strong>{{ $attempt->score }}</strong>
                                    <span class="sg-text-muted">/ {{ $attempt->total_questions }}</span>
                                </td>
                                <td>{{ $attempt->percentage }}%</td>
                                <td>
                                    @if($attempt->is_passed)
                                        <span class="sg-badge sg-badge-success">{{ __('dashboard.passed_badge') }}</span>
                                    @else
                                        <span class="sg-badge sg-badge-danger">{{ __('dashboard.failed_badge') }}</span>
                                    @endif
                                </td>
                                <td class="sg-text-muted">
                                    {{ $attempt->duration ? gmdate('i:s', $attempt->duration) : '—' }}
                                </td>
                                <td class="sg-text-muted">
                                    {{ $attempt->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td>
                                    <a href="{{ route('quiz.attempts.show', $attempt) }}"
                                       class="sg-btn sg-btn-outline sg-btn-sm">
                                        {{ __('enrollments.detail_btn') }}
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="sg-card-section">
                {{ $attempts->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
