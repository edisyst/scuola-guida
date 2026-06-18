@extends('layouts.admin')

@section('title', 'Risultato Simulazione')

@section('content_header')@endsection

@section('content')
<div class="sg-wrapper">

    <div class="sg-header sg-flex-between">
        <div>
            <p class="sg-header-subtitle">{{ __('viewer.simulator.title_full') }}</p>
            <h1 class="sg-header-title">{{ __('viewer.simulator.result_title') }}</h1>
        </div>
        <div class="sg-header-actions">
            <a href="{{ route('simulator.index') }}" class="sg-btn sg-btn-outline sg-btn-sm">
                <i class="fas fa-redo"></i> {{ __('viewer.simulator.new_simulation') }}
            </a>
        </div>
    </div>

    {{-- Riepilogo --}}
    <div class="sg-card mb-4">
        <div class="sg-card-header sg-flex-between">
            <h3 class="sg-card-title">
                <i class="fas {{ $stats['passed'] ? 'fa-check-circle' : 'fa-times-circle' }} mr-2"></i>
                {{ __('viewer.summary') }}
            </h3>
            <span class="badge {{ $stats['passed'] ? 'badge-success' : 'badge-danger' }}" style="font-size:0.9rem;">
                {{ $stats['passed'] ? __('viewer.passed') : __('viewer.failed_sim') }}
            </span>
        </div>
        <div class="sg-card-body">

            <div class="text-center mb-4">
                @if($stats['passed'])
                    <span class="badge badge-success" style="font-size:1.3rem;padding:0.5rem 1.2rem;">{{ __('viewer.passed') }}</span>
                    <p class="text-muted mt-2 mb-0 small">
                        {{ __('viewer.simulator.total_errors_passed', ['count' => $stats['total_errors'], 'max' => $stats['max_errors']]) }}
                    </p>
                @else
                    <span class="badge badge-danger" style="font-size:1.3rem;padding:0.5rem 1.2rem;">{{ __('viewer.failed_sim') }}</span>
                    <p class="text-muted mt-2 mb-0 small">
                        {{ __('viewer.simulator.total_errors_failed', ['count' => $stats['total_errors'], 'max' => $stats['max_errors']]) }}
                    </p>
                @endif
            </div>

            <div class="row text-center">
                <div class="col-6 col-md-4 col-lg-2 mb-3">
                    <p class="sg-label mb-1">{{ __('viewer.score') }}</p>
                    <strong class="d-block">{{ $stats['correct'] }} / {{ $stats['total'] }}</strong>
                </div>
                <div class="col-6 col-md-4 col-lg-2 mb-3">
                    <p class="sg-label mb-1">{{ __('viewer.percentage') }}</p>
                    <strong class="d-block">{{ $stats['percentage'] }}%</strong>
                </div>
                <div class="col-6 col-md-4 col-lg-2 mb-3">
                    <p class="sg-label mb-1">{{ __('viewer.errors') }}</p>
                    <strong class="d-block">{{ $stats['wrong'] }}</strong>
                </div>
                <div class="col-6 col-md-4 col-lg-2 mb-3">
                    <p class="sg-label mb-1">{{ __('viewer.unanswered') }}</p>
                    <strong class="d-block">{{ $stats['not_answered'] }}</strong>
                </div>
                <div class="col-6 col-md-4 col-lg-2 mb-3">
                    <p class="sg-label mb-1">{{ __('viewer.duration') }}</p>
                    <strong class="d-block">{{ $stats['duration_human'] ?? '—' }}</strong>
                </div>
                <div class="col-6 col-md-4 col-lg-2 mb-3">
                    <p class="sg-label mb-1">{{ __('viewer.date') }}</p>
                    <strong class="d-block">{{ $attempt->created_at->format('d/m/Y H:i') }}</strong>
                </div>
            </div>

            <div class="progress mt-3" style="height:10px;">
                <div class="progress-bar {{ $stats['passed'] ? 'bg-success' : 'bg-danger' }}"
                     role="progressbar"
                     style="width:{{ $stats['percentage'] }}%"
                     aria-valuenow="{{ $stats['percentage'] }}"
                     aria-valuemin="0" aria-valuemax="100"></div>
            </div>

        </div>
    </div>

    {{-- Lista domande --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-list me-1"></i> {{ __('viewer.simulator.answered_questions') }}</h3>
        </div>
        <div class="card-body p-0">
            @forelse($rows as $i => $row)
                <div class="d-flex align-items-start p-3 border-bottom">
                    <div class="me-3 text-center" style="min-width:48px;">
                        <span class="badge {{ $row['is_correct'] ? 'bg-success' : 'bg-danger' }}"
                              style="font-size:0.9rem;">
                            {{ $row['position'] ?? ($i + 1) }}
                        </span>
                    </div>
                    <div class="flex-grow-1">
                        <p class="mb-1">{{ $row['localized_text'] ?? $row['question']->question }}</p>
                        <div class="small text-muted">
                            @if($row['question']->category)
                                <i class="fas fa-tag me-1"></i>{{ $row['question']->category->getLocalizedName() }}
                                &nbsp;&bull;&nbsp;
                            @endif
                            {{ __('viewer.simulator.given_answer') }}:
                            <strong>{{ $row['user_answer'] === 1 ? __('viewer.answer_true') : __('viewer.answer_false') }}</strong>
                            &nbsp;&bull;&nbsp;
                            {{ __('viewer.simulator.correct_answer') }}:
                            <strong>{{ $row['correct_answer'] === 1 ? __('viewer.answer_true') : __('viewer.answer_false') }}</strong>
                        </div>
                    </div>
                    <div class="ms-3">
                        @if($row['is_correct'])
                            <i class="fas fa-check-circle text-success fa-lg"></i>
                        @else
                            <i class="fas fa-times-circle text-danger fa-lg"></i>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-muted p-3 mb-0">{{ __('viewer.simulator.no_answers') }}</p>
            @endforelse
        </div>
    </div>

</div>
@endsection
