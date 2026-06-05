@extends('layouts.admin')

@section('title', __('review.title'))

@section('content_header')@endsection

@section('content')
<div class="sg-wrapper">

    <div class="sg-header sg-flex-between">
        <div>
            <p class="sg-header-subtitle">{{ __('review.your_study') }}</p>
            <h1 class="sg-header-title">
                <i class="fas fa-exclamation-triangle mr-2"></i>{{ __('review.title') }}
            </h1>
            <p class="sg-text-muted sg-mt-1">
                {{ __('review.subtitle') }}
            </p>
        </div>
    </div>

    {{-- Filtri --}}
    <div class="card mb-3">
        <div class="card-body py-2">
            <form action="{{ route('viewer.review-errors.index') }}" method="GET" class="form-row align-items-center">

                <div class="col-auto mb-1">
                    <select name="category_id" class="form-control form-control-sm">
                        <option value="">{{ __('review.all_categories') }}</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}"
                                {{ $categoryId === $category->id ? 'selected' : '' }}>
                                {{ $category->getLocalizedName() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                @unless($showLearned)
                <div class="col-auto mb-1">
                    <select name="last_attempts" class="form-control form-control-sm">
                        @foreach([10, 20, 30, 50] as $n)
                            <option value="{{ $n }}" {{ $lastAttempts === $n ? 'selected' : '' }}>
                                {{ __('review.last_n_attempts', ['n' => $n]) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endunless

                <div class="col-auto mb-1">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="show_learned"
                               name="show_learned" value="1"
                               {{ $showLearned ? 'checked' : '' }}
                               onchange="this.form.submit()">
                        <label class="form-check-label" for="show_learned">
                            {{ __('review.show_learned') }}
                            @if($learnedCount > 0)
                                <span class="badge badge-secondary ml-1">{{ $learnedCount }}</span>
                            @endif
                        </label>
                    </div>
                </div>

                @unless($showLearned)
                <div class="col-auto mb-1">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="fas fa-search"></i> {{ __('common.filter') }}
                    </button>
                    <a href="{{ route('viewer.review-errors.index') }}" class="btn btn-sm btn-outline-secondary ml-1">
                        <i class="fas fa-times"></i> Reset
                    </a>
                </div>
                @endunless

            </form>
        </div>
    </div>

    {{-- Empty state --}}
    @if($errors->isEmpty())
        <div class="text-center py-5 text-muted">
            @if($showLearned)
                <i class="fas fa-graduation-cap fa-3x mb-3"></i>
                <p class="mb-1 font-weight-bold">{{ __('review.no_learned') }}</p>
                <p class="mb-3">{{ __('review.no_learned_tip') }}</p>
                <a href="{{ route('viewer.review-errors.index') }}" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-exclamation-triangle"></i> {{ __('review.see_errors') }}
                </a>
            @else
                <i class="fas fa-check-circle fa-3x mb-3 text-success"></i>
                <p class="mb-1 font-weight-bold">{{ __('review.no_errors') }}</p>
                <p class="mb-3 text-muted">
                    @if($categoryId)
                        {{ __('review.no_errors_category', ['n' => $lastAttempts]) }}
                    @else
                        {{ __('review.no_errors_general') }}
                    @endif
                </p>
                <a href="{{ route('study.index') }}" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-graduation-cap"></i> {{ __('review.go_study') }}
                </a>
            @endif
        </div>
    @else

        {{-- Lista domande --}}
        @foreach($errors as $item)
        @php
            $question    = $item['question'];
            $version     = $item['version'] ?? null;
            $errorCount  = $item['error_count'];
            $lastWrongAt = $item['last_wrong_at'];
            $category    = $item['category'];
            $isLearned   = $showLearned;
            $isHistorical = $version !== null && (
                $version->question   !== $question->question   ||
                (bool) $version->is_true !== (bool) $question->is_true ||
                $version->image      !== $question->image      ||
                $version->category_id !== $question->category_id
            );
            $displayVersion = $isHistorical ? $version : $question;

            if ($errorCount !== null) {
                $badgeClass = match(true) {
                    $errorCount >= 6 => 'badge-danger',
                    $errorCount >= 3 => 'badge-warning',
                    default          => 'badge-secondary',
                };
            }
        @endphp
        <div class="card mb-2">
            <div class="card-header d-flex justify-content-between align-items-center py-2">
                <div>
                    @if($category)
                        <span class="badge badge-info mr-1">{{ $category->getLocalizedName() }}</span>
                    @endif
                    @if($errorCount !== null)
                        <span class="badge {{ $badgeClass }}">
                            {{ trans_choice('review.wrong_n_times', $errorCount, ['count' => $errorCount]) }}
                        </span>
                    @else
                        <span class="badge badge-success">
                            <i class="fas fa-graduation-cap"></i> {{ __('review.mark_learned') }}
                        </span>
                    @endif
                    @if($isHistorical)
                        <span class="badge badge-secondary ml-1"
                              data-toggle="tooltip"
                              title="{{ __('viewer.quiz.historical_tooltip') }}">
                            <i class="fas fa-history"></i> {{ __('viewer.quiz.historical_badge') }}
                        </span>
                    @endif
                </div>
                @if($lastWrongAt)
                    <small class="text-muted">{{ __('review.last_wrong') }} {{ $lastWrongAt->diffForHumans() }}</small>
                @endif
            </div>
            <div class="card-body py-2">
                <p class="mb-1"
                   data-toggle="tooltip"
                   title="{{ $displayVersion->question }}"
                   style="cursor:default; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                    {{ Str::limit($displayVersion->question, 120) }}
                </p>
                <small class="text-muted">
                    {{ __('review.correct_answer_label') }}
                    @if($displayVersion->is_true)
                        <strong class="text-success"><i class="fas fa-check"></i> {{ __('viewer.answer_true_full') }}</strong>
                    @else
                        <strong class="text-danger"><i class="fas fa-times"></i> {{ __('viewer.answer_false_full') }}</strong>
                    @endif
                </small>
            </div>
            <div class="card-footer py-2 d-flex justify-content-between align-items-center">
                <a href="{{ route('study.index', ['category_id' => $question->category_id]) }}"
                   class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-graduation-cap"></i> {{ __('review.study_category') }}
                </a>

                @if($isLearned)
                    {{-- Reinserisci negli errori --}}
                    <div x-data>
                        <form method="POST"
                              action="{{ route('viewer.review-errors.learned.destroy', $question) }}"
                              x-ref="unmarkForm">
                            @csrf
                            @method('DELETE')
                            <button type="button"
                                    class="btn btn-sm btn-outline-secondary"
                                    @click="if(confirm('{{ __('review.unmark_confirm') }}')) $refs.unmarkForm.submit()">
                                <i class="fas fa-undo"></i> {{ __('review.unmark_learned') }}
                            </button>
                        </form>
                    </div>
                @else
                    {{-- Marca come imparata --}}
                    <div x-data>
                        <form method="POST"
                              action="{{ route('viewer.review-errors.learned.store', $question) }}"
                              x-ref="markForm">
                            @csrf
                            <button type="button"
                                    class="btn btn-sm btn-outline-success"
                                    @click="if(confirm('{{ __('review.mark_confirm') }}')) $refs.markForm.submit()">
                                <i class="fas fa-graduation-cap"></i> {{ __('review.mark_learned') }}
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
        @endforeach

        {{-- Riepilogo --}}
        <div class="text-muted small mt-3 mb-2">
            @if($showLearned)
                {{ trans_choice('review.learned_count', $errors->count(), ['count' => $errors->count()]) }}.
            @else
                <strong>{{ $errors->count() }}</strong> {{ trans_choice('review.errors_count', $errors->count(), ['count' => $errors->count()]) }}
                @if($learnedCount > 0)
                    &mdash; <strong>{{ $learnedCount }}</strong> {{ trans_choice('review.learned_count', $learnedCount, ['count' => $learnedCount]) }}
                    (<a href="{{ route('viewer.review-errors.index', ['show_learned' => 1] + ($categoryId ? ['category_id' => $categoryId] : [])) }}">{{ __('review.see_learned') }}</a>).
                @endif
            @endif
        </div>

    @endif

</div>
@endsection

@push('scripts')
<script>
$(function () {
    $('[data-toggle="tooltip"]').tooltip({ placement: 'top' });
});
</script>
@endpush
