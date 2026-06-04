@extends('layouts.admin')

@section('title', 'Risultato Simulazione')

@section('content_header')@endsection

@section('content')
<div class="sg-wrapper">

    <div class="sg-header sg-flex-between">
        <div>
            <p class="sg-header-subtitle">Simulatore Esame Patente B</p>
            <h1 class="sg-header-title">Risultato simulazione</h1>
        </div>
        <div class="sg-header-actions">
            <a href="{{ route('simulator.index') }}" class="sg-btn sg-btn-outline sg-btn-sm">
                <i class="fas fa-redo"></i> Nuova simulazione
            </a>
        </div>
    </div>

    {{-- Riepilogo --}}
    <div class="card {{ $stats['passed'] ? 'card-success' : 'card-danger' }} mb-4">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas {{ $stats['passed'] ? 'fa-check-circle' : 'fa-times-circle' }} mr-2"></i>
                Riepilogo
            </h3>
        </div>
        <div class="card-body">

            <div class="text-center mb-4">
                @if($stats['passed'])
                    <span class="badge badge-success" style="font-size:1.3rem;padding:0.5rem 1.2rem;">PROMOSSO</span>
                    <p class="text-muted mt-2 mb-0 small">
                        Errori totali: {{ $stats['total_errors'] }} su {{ $stats['max_errors'] }} consentiti
                    </p>
                @else
                    <span class="badge badge-danger" style="font-size:1.3rem;padding:0.5rem 1.2rem;">NON SUPERATO</span>
                    <p class="text-muted mt-2 mb-0 small">
                        Errori totali: {{ $stats['total_errors'] }} (max consentiti: {{ $stats['max_errors'] }})
                    </p>
                @endif
            </div>

            <div class="row text-center">
                <div class="col-6 col-md-4 col-lg-2 mb-3">
                    <p class="sg-label mb-1">Punteggio</p>
                    <strong class="d-block">{{ $stats['correct'] }} / {{ $stats['total'] }}</strong>
                </div>
                <div class="col-6 col-md-4 col-lg-2 mb-3">
                    <p class="sg-label mb-1">Percentuale</p>
                    <strong class="d-block">{{ $stats['percentage'] }}%</strong>
                </div>
                <div class="col-6 col-md-4 col-lg-2 mb-3">
                    <p class="sg-label mb-1">Errori</p>
                    <strong class="d-block">{{ $stats['wrong'] }}</strong>
                </div>
                <div class="col-6 col-md-4 col-lg-2 mb-3">
                    <p class="sg-label mb-1">Non risposto</p>
                    <strong class="d-block">{{ $stats['not_answered'] }}</strong>
                </div>
                <div class="col-6 col-md-4 col-lg-2 mb-3">
                    <p class="sg-label mb-1">Durata</p>
                    <strong class="d-block">{{ $stats['duration_human'] ?? '—' }}</strong>
                </div>
                <div class="col-6 col-md-4 col-lg-2 mb-3">
                    <p class="sg-label mb-1">Data</p>
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
            <h3 class="card-title"><i class="fas fa-list me-1"></i> Domande risposte</h3>
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
                                <i class="fas fa-tag me-1"></i>{{ $row['question']->category->name }}
                                &nbsp;&bull;&nbsp;
                            @endif
                            Risposta data:
                            <strong>{{ $row['user_answer'] === 1 ? 'VERO' : 'FALSO' }}</strong>
                            &nbsp;&bull;&nbsp;
                            Risposta corretta:
                            <strong>{{ $row['correct_answer'] === 1 ? 'VERO' : 'FALSO' }}</strong>
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
                <p class="text-muted p-3 mb-0">Nessuna risposta registrata.</p>
            @endforelse
        </div>
    </div>

</div>
@endsection
