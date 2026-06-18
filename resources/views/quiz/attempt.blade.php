@extends('layouts.admin')

@section('title', 'Dettaglio Tentativo')

@section('content_header')@endsection

@section('content')
<div class="sg-wrapper">

    @if(auth()->id() !== $attempt->user_id)
    <div class="alert alert-info alert-dismissible fade show mb-3" role="alert">
        <i class="fas fa-info-circle mr-1"></i>
        {{ __('viewer.quiz.viewing_attempt') }} <strong>{{ $attempt->user->name }}</strong>.
        <button type="button" class="close" data-dismiss="alert">
            <span>&times;</span>
        </button>
    </div>
    @endif

    <div class="sg-header sg-flex-between">
        <div>
            <p class="sg-header-subtitle">{{ __('viewer.quiz.attempt_review') }}</p>
            <h1 class="sg-header-title">{{ $quiz->title }}</h1>
        </div>
        <div class="sg-header-actions">
            <a href="{{ route('quiz.attempts.index') }}" class="sg-btn sg-btn-outline sg-btn-sm">
                <i class="fas fa-arrow-left"></i> {{ __('viewer.quiz.back_to_history') }}
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
                {{ $stats['passed'] ? __('viewer.passed') : __('viewer.failed_quiz') }}
            </span>
        </div>
        <div class="sg-card-body">

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
                    <strong class="d-block">{{ $stats['wrong'] }} / {{ $quiz->max_errors ?? '—' }}</strong>
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
                     aria-valuemin="0"
                     aria-valuemax="100">
                </div>
            </div>

        </div>
    </div>

    {{-- Revisione domande --}}
    @foreach($questions as $item)
    @php
        $borderClass = match(true) {
            $item['is_correct'] === true  => 'card-outline card-success',
            $item['is_correct'] === false => 'card-outline card-danger',
            default                       => 'card-outline card-warning',
        };
    @endphp
    <div class="card {{ $borderClass }} mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="font-weight-normal">
                {{ __('viewer.question_label') }} {{ $loop->iteration }} {{ __('viewer.of') }} {{ $stats['total'] }}
            </span>
            @if($item['question']->category)
                <span class="badge badge-info">{{ $item['question']->category->getLocalizedName() }}</span>
            @endif
        </div>
        <div class="card-body">
            @php
                $displayQuestion = $item['version'] ?? $item['question'];
                $displayImage    = $displayQuestion->image ?? null;
            @endphp
            <p class="font-weight-bold mb-3">
                {{ $displayQuestion->question }}
                @if($item['is_historical'])
                    <span class="badge badge-secondary ml-1"
                          data-toggle="tooltip"
                          title="{{ __('viewer.quiz.historical_tooltip') }}">
                        <i class="fas fa-history mr-1"></i>{{ __('viewer.quiz.historical_badge') }}
                    </span>
                @endif
            </p>

            @if($displayImage)
            <div class="mb-3 text-center">
                <img src="{{ Storage::url($displayImage) }}"
                     alt="Immagine domanda"
                     class="img-fluid rounded shadow-sm"
                     style="width:500px;max-width:100%;">
            </div>
            @endif

            <p class="mb-1">
                {{ __('viewer.quiz.your_answer') }}
                @if($item['user_answer'] === null)
                    <em class="text-muted">{{ __('viewer.unanswered') }}</em>
                @elseif($item['user_answer'] === 1)
                    <strong class="{{ $item['is_correct'] ? 'text-success' : 'text-danger' }}">{{ __('viewer.answer_true_full') }}</strong>
                @else
                    <strong class="{{ $item['is_correct'] ? 'text-success' : 'text-danger' }}">{{ __('viewer.answer_false_full') }}</strong>
                @endif
            </p>
            <p class="mb-0">
                {{ __('viewer.quiz.correct_answer') }}
                <strong>{{ $item['correct_answer'] === 1 ? __('viewer.answer_true_full') : __('viewer.answer_false_full') }}</strong>
            </p>

            @if($item['time_spent'] !== null)
            <p class="text-right text-muted small mb-0 mt-2">{{ __('viewer.quiz.time_spent') }} {{ $item['time_spent'] }}s</p>
            @endif

            @auth @if(auth()->user()->isViewer())
            <div class="mt-2 d-flex justify-content-end gap-2">
                <livewire:bookmark-button :question-id="$item['question']->id" :key="'bm-'.$item['question']->id" />
                <livewire:report-button :question-id="$item['question']->id" :key="'report-'.$item['question']->id" />
            </div>
            @endif @endauth
        </div>
    </div>
    @endforeach

    <div class="text-center mb-4">
        <a href="{{ route('quiz.attempts.index') }}" class="sg-btn sg-btn-outline">
            <i class="fas fa-arrow-left"></i> {{ __('viewer.quiz.back_to_history') }}
        </a>
    </div>

</div>
@endsection

@push('scripts')
<script>
$(function () {
    $('[data-toggle="tooltip"]').tooltip();
});
</script>
@endpush
