<div>
    @if($isFinished)
        {{-- Schermata finale --}}
        <div class="text-center p-5">
            <i class="fas fa-check-circle text-success" style="font-size:48px;"></i>
            <h3 class="mt-3">{{ __('review.session_complete') }}</h3>
            <p class="text-muted">
                {{ __('review.session_stats', ['correct' => $sessionStats['correct'], 'wrong' => $sessionStats['wrong'], 'total' => $total]) }}
            </p>
            <div class="mt-4 d-flex justify-content-center" style="gap:1rem;">
                <a href="{{ route('viewer.study-plan.show') }}" class="sg-btn sg-btn-outline">
                    <i class="fas fa-route mr-1"></i> {{ __('menu.piano_studio') }}
                </a>
                <a href="{{ route('viewer.smart-review.session') }}" class="sg-btn sg-btn-primary">
                    <i class="fas fa-redo mr-1"></i> {{ __('viewer.study.new_session') }}
                </a>
            </div>
        </div>

    @elseif($currentQuestion)

        {{-- Progress bar --}}
        <div class="mb-4">
            <div class="d-flex justify-content-between mb-1">
                <small class="text-muted">{{ __('viewer.question_label') }} {{ $currentIndex + 1 }} {{ __('viewer.of') }} {{ $total }}</small>
                <small class="text-muted">{{ $currentQuestion->category?->getLocalizedName() ?? '' }}</small>
            </div>
            <div class="progress" style="height: 8px;">
                <div class="progress-bar bg-primary"
                     role="progressbar"
                     style="width: {{ $total > 0 ? ($currentIndex / $total) * 100 : 0 }}%"
                     aria-valuenow="{{ $currentIndex }}"
                     aria-valuemin="0"
                     aria-valuemax="{{ $total }}">
                </div>
            </div>
        </div>

        {{-- Card domanda --}}
        <div class="sg-card mb-4">
            <div class="sg-card-body p-4">
                @if($currentQuestion->image)
                    <div class="text-center mb-3">
                        <img src="{{ asset('storage/images/' . $currentQuestion->image) }}"
                             alt="{{ __('viewer.study.image_alt') }}"
                             class="img-fluid"
                             style="max-height: 250px; object-fit: contain;">
                    </div>
                @endif
                <p class="h5 text-center mb-0">{{ $localizedText ?? $currentQuestion->question }}</p>
            </div>
        </div>

        @if(!$showFeedback)
            {{-- Bottoni risposta --}}
            <div class="d-flex justify-content-center" style="gap: 1rem;">
                <button wire:click="answer(1)"
                        wire:loading.attr="disabled"
                        class="sg-btn sg-btn-success"
                        style="min-width: 140px; font-size: 1.1rem; padding: .6rem 1.5rem;">
                    <span wire:loading.remove wire:target="answer">
                        <i class="fas fa-check mr-1"></i> {{ __('viewer.answer_true_full') }}
                    </span>
                    <span wire:loading wire:target="answer">
                        <i class="fas fa-spinner fa-spin"></i>
                    </span>
                </button>
                <button wire:click="answer(0)"
                        wire:loading.attr="disabled"
                        class="sg-btn sg-btn-danger"
                        style="min-width: 140px; font-size: 1.1rem; padding: .6rem 1.5rem;">
                    <span wire:loading.remove wire:target="answer">
                        <i class="fas fa-times mr-1"></i> {{ __('viewer.answer_false_full') }}
                    </span>
                    <span wire:loading wire:target="answer">
                        <i class="fas fa-spinner fa-spin"></i>
                    </span>
                </button>
            </div>
        @else
            {{-- Feedback dopo risposta --}}
            <div class="text-center mb-3">
                @if($lastAnswerCorrect)
                    <span class="sg-badge sg-badge-success mb-2 d-inline-block">
                        <i class="fas fa-check mr-1"></i> {{ __('viewer.correct_feedback') }}
                    </span>
                @else
                    <span class="sg-badge sg-badge-danger mb-2 d-inline-block">
                        <i class="fas fa-times mr-1"></i> {{ __('viewer.wrong_feedback') }}
                    </span>
                @endif
                <br>
                <span class="sg-badge sg-badge-info d-inline-block mb-2">
                    {{ __('viewer.study.correct_is') }} <strong>{{ $currentQuestion->is_true == 1 ? __('viewer.answer_true_full') : __('viewer.answer_false_full') }}</strong>
                </span>
                <br>
                <small class="text-muted">
                    <i class="fas fa-calendar-alt mr-1"></i>
                    {{ trans_choice('review.next_review', $lastIntervalDays, ['days' => $lastIntervalDays]) }}
                </small>
            </div>
            <div class="d-flex justify-content-center" style="gap: 1rem; flex-wrap: wrap;">
                <button wire:click="nextQuestion"
                        wire:loading.attr="disabled"
                        class="sg-btn sg-btn-primary"
                        style="min-width: 140px;">
                    <span wire:loading.remove wire:target="nextQuestion">
                        <i class="fas fa-arrow-right mr-1"></i> {{ __('viewer.next') }}
                    </span>
                    <span wire:loading wire:target="nextQuestion">
                        <i class="fas fa-spinner fa-spin"></i>
                    </span>
                </button>
                <button wire:click="markCurrentAsLearned"
                        wire:loading.attr="disabled"
                        wire:confirm="{{ __('review.confirm_mark_learned') }}"
                        class="sg-btn sg-btn-light"
                        style="min-width: 140px;">
                    <span wire:loading.remove wire:target="markCurrentAsLearned">
                        <i class="fas fa-graduation-cap mr-1"></i> {{ __('review.learned_btn') }}
                    </span>
                    <span wire:loading wire:target="markCurrentAsLearned">
                        <i class="fas fa-spinner fa-spin"></i>
                    </span>
                </button>
            </div>
        @endif

    @else
        {{-- Empty state --}}
        <div class="text-center p-5">
            <i class="fas fa-check-double text-success fa-3x text-muted"></i>
            <h3 class="mt-3">{{ __('review.no_due') }}</h3>
            <p class="text-muted">{{ __('review.no_due_desc') }}</p>
            <a href="{{ route('viewer.study-plan.show') }}" class="sg-btn sg-btn-primary mt-2">
                <i class="fas fa-route mr-1"></i> {{ __('menu.piano_studio') }}
            </a>
        </div>
    @endif
</div>
