<div>
    @if($completed)

        <div class="text-center p-5">
            <i class="fas fa-check-circle text-success" style="font-size:48px;"></i>
            <h3 class="mt-3">{{ __('review.diagnostic_complete') }}</h3>
            <p class="text-muted">{{ __('review.diagnostic_processed') }}</p>
            <a href="{{ route('viewer.study-plan.show') }}" class="sg-btn sg-btn-primary mt-3">
                <i class="fas fa-route"></i> {{ __('review.diagnostic_go_plan') }}
            </a>
        </div>

    @elseif($currentQuestion)

        {{-- Progress bar --}}
        <div class="mb-4">
            <div class="d-flex justify-content-between mb-1">
                <small class="text-muted">Domanda {{ $currentIndex + 1 }} di {{ $total }}</small>
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

        {{-- Question card --}}
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

        {{-- Buttons --}}
        <div class="d-flex justify-content-center" style="gap: 1rem;">
            <button wire:click="submitAnswer(1)"
                    wire:loading.attr="disabled"
                    class="sg-btn sg-btn-success"
                    style="min-width: 140px; font-size: 1.1rem; padding: .6rem 1.5rem;">
                <span wire:loading.remove wire:target="submitAnswer">
                    <i class="fas fa-check mr-1"></i> {{ __('viewer.answer_true_full') }}
                </span>
                <span wire:loading wire:target="submitAnswer">
                    <i class="fas fa-spinner fa-spin"></i>
                </span>
            </button>

            <button wire:click="submitAnswer(0)"
                    wire:loading.attr="disabled"
                    class="sg-btn sg-btn-danger"
                    style="min-width: 140px; font-size: 1.1rem; padding: .6rem 1.5rem;">
                <span wire:loading.remove wire:target="submitAnswer">
                    <i class="fas fa-times mr-1"></i> {{ __('viewer.answer_false_full') }}
                </span>
                <span wire:loading wire:target="submitAnswer">
                    <i class="fas fa-spinner fa-spin"></i>
                </span>
            </button>
        </div>

    @else

        {{-- Nessuna domanda disponibile --}}
        <div class="text-center p-5">
            <i class="fas fa-inbox text-muted" style="font-size:48px;"></i>
            <h3 class="mt-3">{{ __('review.diagnostic_no_questions') }}</h3>
            <p class="text-muted">{{ __('review.diagnostic_no_questions_desc') }}</p>
            <a href="{{ route('study.index') }}" class="sg-btn sg-btn-primary mt-2">
                <i class="fas fa-graduation-cap mr-1"></i> {{ __('menu.modalita_studio') }}
            </a>
        </div>

    @endif
</div>
