@extends('layouts.admin')

@section('title', 'Dettaglio Tentativo')

@section('content_header')@endsection

@section('content')
<div class="sg-wrapper">

    @if(auth()->id() !== $attempt->user_id)
    <div class="alert alert-info alert-dismissible fade show mb-3" role="alert">
        <i class="fas fa-info-circle mr-1"></i>
        Stai visualizzando il tentativo di <strong>{{ $attempt->user->name }}</strong>.
        <button type="button" class="close" data-dismiss="alert">
            <span>&times;</span>
        </button>
    </div>
    @endif

    <div class="sg-header sg-flex-between">
        <div>
            <p class="sg-header-subtitle">Revisione tentativo</p>
            <h1 class="sg-header-title">{{ $quiz->title }}</h1>
        </div>
        <div class="sg-header-actions">
            <a href="{{ route('quiz.attempts.index') }}" class="sg-btn sg-btn-outline sg-btn-sm">
                <i class="fas fa-arrow-left"></i> Torna allo storico
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
                @else
                    <span class="badge badge-danger" style="font-size:1.3rem;padding:0.5rem 1.2rem;">RIMANDATO</span>
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
                    <strong class="d-block">{{ $stats['wrong'] }} / {{ $quiz->max_errors ?? '—' }}</strong>
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
                Domanda {{ $loop->iteration }} di {{ $stats['total'] }}
            </span>
            @if($item['question']->category)
                <span class="badge badge-info">{{ $item['question']->category->name }}</span>
            @endif
        </div>
        <div class="card-body">
            <p class="font-weight-bold mb-3">{{ $item['question']->question }}</p>

            @if($item['question']->image)
            <div class="mb-3">
                <img src="{{ Storage::url($item['question']->image) }}"
                     alt="Immagine domanda"
                     class="img-fluid rounded shadow-sm"
                     style="max-width:100%;">
            </div>
            @endif

            <p class="mb-1">
                La tua risposta:
                @if($item['user_answer'] === null)
                    <em class="text-muted">Non risposto</em>
                @elseif($item['user_answer'] === 1)
                    <strong class="{{ $item['is_correct'] ? 'text-success' : 'text-danger' }}">Vero</strong>
                @else
                    <strong class="{{ $item['is_correct'] ? 'text-success' : 'text-danger' }}">Falso</strong>
                @endif
            </p>
            <p class="mb-0">
                Risposta corretta:
                <strong>{{ $item['correct_answer'] === 1 ? 'Vero' : 'Falso' }}</strong>
            </p>

            @if($item['time_spent'] !== null)
            <p class="text-right text-muted small mb-0 mt-2">Tempo: {{ $item['time_spent'] }}s</p>
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
            <i class="fas fa-arrow-left"></i> Torna allo storico
        </a>
    </div>

</div>
@endsection
