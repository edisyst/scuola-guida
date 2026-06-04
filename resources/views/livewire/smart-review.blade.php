<div>
    @if($isFinished)
        {{-- Schermata finale --}}
        <div class="text-center p-5">
            <i class="fas fa-check-circle text-success" style="font-size:48px;"></i>
            <h3 class="mt-3">Sessione completata!</h3>
            <p class="text-muted">
                <span class="text-success font-weight-bold">{{ $sessionStats['correct'] }} corrette</span>
                &mdash;
                <span class="text-danger font-weight-bold">{{ $sessionStats['wrong'] }} sbagliate</span>
                su {{ $total }} domande.
            </p>
            <div class="mt-4 d-flex justify-content-center" style="gap:1rem;">
                <a href="{{ route('viewer.study-plan.show') }}" class="sg-btn sg-btn-outline">
                    <i class="fas fa-route mr-1"></i> Piano di studio
                </a>
                <a href="{{ route('viewer.smart-review.session') }}" class="sg-btn sg-btn-primary">
                    <i class="fas fa-redo mr-1"></i> Nuova sessione
                </a>
            </div>
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

        {{-- Card domanda --}}
        <div class="sg-card mb-4">
            <div class="sg-card-body p-4">
                @if($currentQuestion->image)
                    <div class="text-center mb-3">
                        <img src="{{ asset('storage/images/' . $currentQuestion->image) }}"
                             alt="Immagine domanda"
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
                        <i class="fas fa-check mr-1"></i> Vero
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
                        <i class="fas fa-times mr-1"></i> Falso
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
                        <i class="fas fa-check mr-1"></i> Risposta corretta!
                    </span>
                @else
                    <span class="sg-badge sg-badge-danger mb-2 d-inline-block">
                        <i class="fas fa-times mr-1"></i> Risposta sbagliata
                    </span>
                @endif
                <br>
                @if($currentQuestion->is_true == 1)
                    <span class="sg-badge sg-badge-info d-inline-block mb-2">
                        La risposta corretta è: <strong>Vero</strong>
                    </span>
                @else
                    <span class="sg-badge sg-badge-info d-inline-block mb-2">
                        La risposta corretta è: <strong>Falso</strong>
                    </span>
                @endif
                <br>
                <small class="text-muted">
                    <i class="fas fa-calendar-alt mr-1"></i>
                    Prossima revisione tra
                    <strong>{{ $lastIntervalDays }} {{ $lastIntervalDays === 1 ? 'giorno' : 'giorni' }}</strong>
                </small>
            </div>
            <div class="d-flex justify-content-center" style="gap: 1rem; flex-wrap: wrap;">
                <button wire:click="nextQuestion"
                        wire:loading.attr="disabled"
                        class="sg-btn sg-btn-primary"
                        style="min-width: 140px;">
                    <span wire:loading.remove wire:target="nextQuestion">
                        <i class="fas fa-arrow-right mr-1"></i> Prossima
                    </span>
                    <span wire:loading wire:target="nextQuestion">
                        <i class="fas fa-spinner fa-spin"></i>
                    </span>
                </button>
                <button wire:click="markCurrentAsLearned"
                        wire:loading.attr="disabled"
                        wire:confirm="Marcare questa domanda come imparata la escluderà dal ripasso intelligente. Continuare?"
                        class="sg-btn sg-btn-light"
                        style="min-width: 140px;">
                    <span wire:loading.remove wire:target="markCurrentAsLearned">
                        <i class="fas fa-graduation-cap mr-1"></i> Imparata
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
            <h3 class="mt-3">Nessuna domanda in scadenza</h3>
            <p class="text-muted">Ottimo lavoro! Non hai domande da ripassare per ora.</p>
            <a href="{{ route('viewer.study-plan.show') }}" class="sg-btn sg-btn-primary mt-2">
                <i class="fas fa-route mr-1"></i> Piano di studio
            </a>
        </div>
    @endif
</div>
