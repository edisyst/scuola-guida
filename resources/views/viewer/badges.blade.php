@extends('layouts.admin')
@section('title', 'I miei badge')
@section('content_header')@endsection

@section('content')
<div class="sg-wrapper">

    <div class="sg-header sg-flex-between">
        <div>
            <p class="sg-header-subtitle">Traguardi e riconoscimenti</p>
            <h1 class="sg-header-title">
                <i class="fas fa-award mr-2"></i> I miei badge
            </h1>
        </div>
        <div class="sg-header-actions">
            <a href="{{ route('dashboard') }}" class="sg-btn sg-btn-light sg-btn-sm">
                <i class="fas fa-arrow-left"></i> Dashboard
            </a>
        </div>
    </div>

    {{-- Streak stats --}}
    <div class="row sg-grid-row">
        <div class="col-md-4 sg-grid-col">
            <div class="sg-stat-card">
                <div class="sg-stat-icon" style="background: linear-gradient(135deg, #ff6b35, #f7c59f);">
                    <i class="fas fa-fire"></i>
                </div>
                <div>
                    <div class="sg-stat-value">{{ $currentStreak }}</div>
                    <div class="sg-stat-label">
                        Streak attuale
                        @if($currentStreak > 0)
                            <small class="sg-text-muted d-block">
                                {{ $currentStreak === 1 ? 'giorno' : 'giorni' }} consecutivi
                            </small>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4 sg-grid-col">
            <div class="sg-stat-card">
                <div class="sg-stat-icon" style="background: linear-gradient(135deg, #a8dadc, #457b9d);">
                    <i class="fas fa-trophy"></i>
                </div>
                <div>
                    <div class="sg-stat-value">{{ $longestStreak }}</div>
                    <div class="sg-stat-label">
                        Streak record
                        <small class="sg-text-muted d-block">migliore di sempre</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4 sg-grid-col">
            <div class="sg-stat-card">
                <div class="sg-stat-icon grad-green">
                    <i class="fas fa-medal"></i>
                </div>
                <div>
                    <div class="sg-stat-value">{{ $earnedBadges->count() }} / {{ count($allBadges) }}</div>
                    <div class="sg-stat-label">
                        Badge ottenuti
                        <small class="sg-text-muted d-block">su {{ count($allBadges) }} disponibili</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Progress bar --}}
    @if(count($allBadges) > 0)
        @php $pct = round($earnedBadges->count() / count($allBadges) * 100) @endphp
        <div class="sg-card sg-mt-2 mb-4">
            <div class="sg-card-body py-3">
                <div class="d-flex justify-content-between mb-1">
                    <small class="font-weight-bold">Progressione badge</small>
                    <small class="text-muted">{{ $pct }}%</small>
                </div>
                <div class="progress" style="height: 10px;">
                    <div class="progress-bar bg-success" style="width: {{ $pct }}%;"></div>
                </div>
            </div>
        </div>
    @endif

    {{-- Badge grid --}}
    <div class="row">
        @foreach($allBadges as $code => $badge)
            @php $earned = $earnedBadges->get($code) @endphp
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100 {{ $earned ? 'border-' . $badge['color'] : 'border-0 bg-light' }}"
                     style="{{ $earned ? '' : 'opacity: 0.55;' }}">
                    <div class="card-body text-center py-4">
                        <div class="mb-3">
                            <span class="fa-stack fa-2x">
                                <i class="fas fa-circle fa-stack-2x text-{{ $earned ? $badge['color'] : 'secondary' }}"></i>
                                <i class="{{ $badge['icon'] }} fa-stack-1x fa-inverse"></i>
                            </span>
                        </div>
                        <h5 class="card-title mb-1 {{ $earned ? '' : 'text-muted' }}">
                            {{ $badge['name'] }}
                        </h5>
                        <p class="card-text small text-muted mb-2">
                            {{ $badge['description'] }}
                        </p>
                        @if($earned)
                            <span class="badge badge-{{ $badge['color'] }}">
                                <i class="fas fa-check-circle mr-1"></i>
                                Ottenuto il {{ $earned->earned_at->format('d/m/Y') }}
                            </span>
                        @else
                            <span class="badge badge-secondary">
                                <i class="fas fa-lock mr-1"></i>
                                Non ancora ottenuto
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if($earnedBadges->isEmpty())
        <div class="sg-card sg-mt-3">
            <div class="sg-card-body sg-text-center p-5">
                <i class="fas fa-award fa-3x text-muted mb-3"></i>
                <h3 class="sg-mt-2">Nessun badge ancora</h3>
                <p class="sg-text-muted">
                    Continua a studiare e completa i quiz per sbloccare i tuoi primi badge!
                </p>
                <a href="{{ route('study.index') }}" class="sg-btn sg-btn-primary sg-mt-2">
                    <i class="fas fa-graduation-cap mr-1"></i> Vai allo studio
                </a>
            </div>
        </div>
    @endif

</div>
@endsection
