@extends('layouts.admin')

@section('title', 'Studio')

@section('content_header')@endsection

@section('content')
@php
    $correct  = (int) $question->is_true;
    $percent  = $total > 0 ? round((($index + 1) / $total) * 100) : 0;
    $imageUrl = $question->image ? \Illuminate\Support\Facades\Storage::url($question->image) : null;
    $prevUrl  = $index > 0      ? route('study.play', ['index' => $index - 1]) : null;
    $nextUrl  = $index < $total - 1 ? route('study.play', ['index' => $index + 1]) : null;
@endphp

<div class="sg-wrapper" style="max-width: 800px; margin: 0 auto;"
     x-data="studyPlay({
        questionId: {{ $question->id }},
        correct: {{ $correct }},
        flagged: {{ $isFlagged ? 'true' : 'false' }},
        flagUrl: '{{ route('study.flag', ['question' => $question->id]) }}',
        csrf: '{{ csrf_token() }}'
     })">

    {{-- ── Header + progress ────────────────────────────────── --}}
    <div class="sg-header sg-flex-between">
        <div>
            <p class="sg-header-subtitle">Modalità Studio</p>
            <h1 class="sg-header-title">Domanda {{ $index + 1 }} di {{ $total }}</h1>
        </div>
        <div>
            <a href="{{ route('study.index') }}" class="sg-btn sg-btn-light sg-btn-sm">
                <i class="fas fa-cog"></i> Cambia sorgente
            </a>
        </div>
    </div>

    <div class="progress sg-mb-3" style="height: 12px;">
        <div class="progress-bar bg-primary"
             role="progressbar"
             style="width: {{ $percent }}%"
             aria-valuenow="{{ $percent }}"
             aria-valuemin="0"
             aria-valuemax="100">{{ $percent }}%</div>
    </div>

    {{-- ── Card domanda ─────────────────────────────────────── --}}
    <div class="card">
        <div class="card-body p-4">

            @if($question->category)
                <span class="badge badge-secondary mb-3">{{ $question->category->name }}</span>
            @endif

            <h4 class="mb-4">{{ $question->question }}</h4>

            @if($imageUrl)
                <div class="text-center mb-4">
                    <img src="{{ $imageUrl }}"
                         alt="Immagine domanda"
                         class="img-fluid rounded shadow-sm"
                         style="max-height: 280px;">
                </div>
            @endif

            <div class="row">
                <div class="col-6">
                    <button type="button"
                            class="btn btn-block btn-lg"
                            :class="answerButtonClass(1)"
                            :disabled="answered"
                            @click="answer(1)">
                        <i class="fas fa-check"></i> VERO
                    </button>
                </div>
                <div class="col-6">
                    <button type="button"
                            class="btn btn-block btn-lg"
                            :class="answerButtonClass(0)"
                            :disabled="answered"
                            @click="answer(0)">
                        <i class="fas fa-times"></i> FALSO
                    </button>
                </div>
            </div>

            {{-- Feedback inline (Alpine, no round-trip) --}}
            <div class="mt-3" x-show="answered" x-cloak>
                <div class="alert"
                     :class="selected === correct ? 'alert-success' : 'alert-danger'">
                    <template x-if="selected === correct">
                        <span><i class="fas fa-check-circle"></i> <strong>Risposta corretta!</strong></span>
                    </template>
                    <template x-if="selected !== correct">
                        <span>
                            <i class="fas fa-times-circle"></i>
                            <strong>Risposta errata.</strong>
                            La risposta corretta è:
                            <strong x-text="correct === 1 ? 'VERO' : 'FALSO'"></strong>
                        </span>
                    </template>
                </div>
            </div>

        </div>

        {{-- ── Footer con navigazione ───────────────────────── --}}
        <div class="card-footer d-flex justify-content-between align-items-center flex-wrap">
            <div>
                @if($prevUrl)
                    <a href="{{ $prevUrl }}" class="sg-btn sg-btn-outline">
                        <i class="fas fa-chevron-left"></i> Precedente
                    </a>
                @else
                    <button class="sg-btn sg-btn-outline" disabled>
                        <i class="fas fa-chevron-left"></i> Precedente
                    </button>
                @endif
            </div>

            <div>
                <button type="button"
                        class="sg-btn"
                        :class="flagged ? 'sg-btn-warning' : 'sg-btn-light'"
                        @click="toggleFlag()">
                    <i class="fas" :class="flagged ? 'fa-bookmark' : 'fa-bookmark'"></i>
                    <span x-text="flagged ? 'Segnata da ripassare' : 'Segna da ripassare'"></span>
                </button>

                <a href="{{ route('study.summary') }}" class="sg-btn sg-btn-dark ml-2">
                    <i class="fas fa-flag-checkered"></i> Termina sessione
                </a>
            </div>

            <div>
                @if($nextUrl)
                    <a href="{{ $nextUrl }}" class="sg-btn sg-btn-primary">
                        Prossima <i class="fas fa-chevron-right"></i>
                    </a>
                @else
                    <a href="{{ route('study.summary') }}" class="sg-btn sg-btn-primary">
                        Vai al riepilogo <i class="fas fa-chevron-right"></i>
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }
</style>
@endsection

@section('js')
    @parent

    {{-- Alpine.js via CDN: il layout admin non bundla resources/js/app.js --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <script>
        function studyPlay(config) {
            return {
                questionId: config.questionId,
                correct: config.correct,
                flagged: config.flagged,
                flagUrl: config.flagUrl,
                csrf: config.csrf,
                selected: null,
                answered: false,

                answer(value) {
                    if (this.answered) return;
                    this.selected = value;
                    this.answered = true;

                    // Registra la risposta lato server per il riepilogo.
                    fetch(this.flagUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': this.csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({ answer: String(value) }),
                    }).catch(() => {});
                },

                toggleFlag() {
                    fetch(this.flagUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': this.csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({ toggle: true }),
                    })
                    .then(r => r.ok ? r.json() : Promise.reject(r))
                    .then(data => {
                        if (data.flagged !== null && data.flagged !== undefined) {
                            this.flagged = data.flagged;
                        }
                    })
                    .catch(() => {
                        if (window.toastr) toastr.error('Errore nel salvataggio del segnalibro');
                    });
                },

                answerButtonClass(value) {
                    if (!this.answered) {
                        return value === 1 ? 'btn-outline-success' : 'btn-outline-danger';
                    }
                    // Mostra sempre la corretta in verde
                    if (value === this.correct) {
                        return 'btn-success';
                    }
                    // Pulsante sbagliato cliccato → rosso
                    if (value === this.selected) {
                        return 'btn-danger';
                    }
                    return value === 1 ? 'btn-outline-success' : 'btn-outline-danger';
                },
            }
        }
    </script>
@endsection
