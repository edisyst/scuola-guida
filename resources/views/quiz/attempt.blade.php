@extends('layouts.admin')

@section('title', 'Risultato Quiz')

@section('content_header')@endsection

@section('content')
<div class="sg-wrapper-sm">

    <div class="sg-header sg-flex-between">
        <div>
            <p class="sg-header-subtitle">Esito del tentativo</p>
            <h1 class="sg-header-title">Risultato Quiz</h1>
        </div>
        @if($attempt->is_passed)
            <span class="sg-badge sg-badge-success">Superato</span>
        @else
            <span class="sg-badge sg-badge-danger">Non superato</span>
        @endif
    </div>

    <div class="sg-card">
        <div class="sg-card-body">

            <div class="sg-text-center sg-mb-3">
                <span class="sg-label">Punteggio</span>
                <div class="sg-score-display">
                    {{ $attempt->score }}<span class="sg-text-muted" style="font-size:1.6rem;font-weight:600;"> / {{ $attempt->total_questions }}</span>
                </div>
                <p class="sg-text-muted sg-mt-1 sg-mb-0">{{ $attempt->percentage }}% di risposte corrette</p>
            </div>

            <div class="row">
                <div class="col-sm-6">
                    <div class="sg-card-section">
                        <span class="sg-label">Esito</span>
                        @if($attempt->is_passed)
                            <strong class="sg-text-success">
                                <i class="fas fa-check-circle"></i> SUPERATO
                            </strong>
                        @else
                            <strong class="sg-text-danger">
                                <i class="fas fa-times-circle"></i> NON SUPERATO
                            </strong>
                        @endif
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="sg-card-section">
                        <span class="sg-label">Tempo impiegato</span>
                        <strong>
                            @if($attempt->duration)
                                {{ gmdate('i:s', $attempt->duration) }}
                            @else
                                —
                            @endif
                        </strong>
                    </div>
                </div>
            </div>

            <div class="sg-mt-3 sg-d-flex sg-gap-2" style="justify-content:center;flex-wrap:wrap;">
                <a href="{{ route('quiz.attempts.index') }}" class="sg-btn sg-btn-outline">
                    <i class="fas fa-list"></i> Tutti i miei tentativi
                </a>
                <a href="{{ route('quiz.play', $attempt->quiz_id ?? 1) }}" class="sg-btn sg-btn-primary">
                    <i class="fas fa-redo"></i> Riprova
                </a>
            </div>

        </div>
    </div>
</div>
@endsection
