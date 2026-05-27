@extends('layouts.admin')

@section('title', 'Piano di studio')

@section('content_header')@endsection

@section('content')
<div class="sg-wrapper">

    <div class="sg-header sg-flex-between">
        <div>
            <p class="sg-header-subtitle">Le categorie ordinate dalla più debole alla più forte</p>
            <h1 class="sg-header-title">
                <i class="fas fa-route mr-2"></i> Piano di studio
            </h1>
        </div>
    </div>

    {{-- Banner diagnostico --}}
    @if($hasDiagnostic)
        <div class="alert alert-success mb-4 d-flex align-items-center justify-content-between flex-wrap" style="gap:.5rem;">
            <div>
                <i class="fas fa-check-circle mr-1"></i>
                Hai completato il test diagnostico — il piano include i tuoi risultati iniziali.
            </div>
            <a href="{{ route('viewer.diagnostic.show') }}" class="sg-btn sg-btn-light sg-btn-sm">
                <i class="fas fa-redo mr-1"></i> Rifai il test
            </a>
        </div>
    @else
        <div class="alert alert-warning mb-4 d-flex align-items-center justify-content-between flex-wrap" style="gap:.5rem;">
            <div>
                <i class="fas fa-lightbulb mr-1"></i>
                Vuoi un punto di partenza più preciso?
                Fai il test diagnostico per personalizzare ulteriormente il piano.
            </div>
            <a href="{{ route('viewer.diagnostic.show') }}" class="sg-btn sg-btn-warning sg-btn-sm">
                <i class="fas fa-stethoscope mr-1"></i> Fai il test diagnostico
            </a>
        </div>
    @endif

    @if($plan->isEmpty())

        {{-- Empty state --}}
        <div class="sg-card">
            <div class="sg-card-body text-center p-5">
                <i class="fas fa-graduation-cap text-muted" style="font-size:48px;"></i>
                <h3 class="mt-3">Inizia il tuo percorso</h3>
                <p class="text-muted">
                    Non hai ancora dati sufficienti per costruire il piano.<br>
                    Fai il test diagnostico oppure completa il tuo primo quiz.
                </p>
                <a href="{{ route('viewer.diagnostic.show') }}" class="sg-btn sg-btn-primary mt-2 mr-2">
                    <i class="fas fa-stethoscope mr-1"></i> Test diagnostico
                </a>
                <a href="{{ route('study.index') }}" class="sg-btn sg-btn-outline mt-2">
                    <i class="fas fa-graduation-cap mr-1"></i> Modalità studio
                </a>
            </div>
        </div>

    @else

        <div class="row sg-grid-row">
            @foreach($plan as $item)
                @php
                    $mastery = $item['mastery'];
                    $progressColor = $mastery < 30 ? 'bg-danger' : ($mastery <= 70 ? 'bg-warning' : 'bg-success');
                    $badgeClass    = $mastery < 30 ? 'sg-badge-danger' : ($mastery <= 70 ? 'sg-badge-warning' : 'sg-badge-success');
                @endphp

                <div class="col-12 col-md-6 col-lg-4 sg-grid-col">
                    <div class="sg-card h-100">
                        <div class="sg-card-body p-3 d-flex flex-column">

                            {{-- Header categoria --}}
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="font-weight-bold mb-0" style="line-height:1.3;">
                                    {{ $item['category']->name }}
                                </h6>
                                <span class="sg-badge {{ $badgeClass }} ml-2" style="white-space:nowrap;">
                                    {{ $mastery }}%
                                </span>
                            </div>

                            {{-- Progress bar mastery --}}
                            <div class="progress mb-2" style="height:6px;">
                                <div class="progress-bar {{ $progressColor }}"
                                     role="progressbar"
                                     style="width: {{ $mastery }}%"
                                     aria-valuenow="{{ $mastery }}"
                                     aria-valuemin="0"
                                     aria-valuemax="100">
                                </div>
                            </div>

                            {{-- Statistiche --}}
                            <p class="sg-text-muted small mb-1">
                                <i class="fas fa-clipboard-list mr-1"></i>
                                {{ $item['attempts_count'] }} {{ Str::plural('risposta', $item['attempts_count']) }} nella storia
                            </p>
                            <p class="small mb-3">
                                <i class="fas fa-info-circle text-muted mr-1"></i>
                                {{ $item['recommended_action'] }}
                            </p>

                            {{-- Azione --}}
                            <div class="mt-auto">
                                <form action="{{ route('study.start') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="source" value="category">
                                    <input type="hidden" name="category_id" value="{{ $item['category']->id }}">
                                    <button type="submit" class="sg-btn sg-btn-primary sg-btn-sm w-100">
                                        <i class="fas fa-play mr-1"></i> Studia ora
                                    </button>
                                </form>
                                @if(($reviewCountByCategory[$item['category']->id] ?? 0) > 0)
                                    <a href="{{ route('viewer.smart-review.session', ['category_id' => $item['category']->id]) }}"
                                       class="sg-btn sg-btn-outline sg-btn-sm w-100 mt-2">
                                        <i class="fas fa-brain mr-1"></i>
                                        Ripassa ({{ $reviewCountByCategory[$item['category']->id] }})
                                    </a>
                                @endif
                            </div>

                        </div>
                    </div>
                </div>
            @endforeach
        </div>

    @endif

</div>
@endsection
