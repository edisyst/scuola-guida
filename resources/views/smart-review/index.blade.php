@extends('layouts.admin')
@section('title', 'Ripasso intelligente')
@section('content_header')@endsection
@section('content')
<div class="sg-wrapper">

    <div class="sg-header sg-flex-between">
        <div>
            <p class="sg-header-subtitle">Algoritmo SM-2 — domande ordinate per urgenza</p>
            <h1 class="sg-header-title">
                <i class="fas fa-brain mr-2"></i> Ripasso intelligente
            </h1>
        </div>
        <div class="sg-header-actions">
            @if($upcoming['due_today'] > 0)
                <a href="{{ route('viewer.smart-review.session') }}" class="sg-btn sg-btn-primary">
                    <i class="fas fa-play mr-1"></i> Inizia ripasso ({{ $upcoming['due_today'] }})
                </a>
            @endif
        </div>
    </div>

    {{-- Stats overview --}}
    <div class="row sg-grid-row">
        <div class="col-lg-3 col-6 sg-grid-col">
            <div class="sg-stat-card">
                <div class="sg-stat-icon grad-blue"><i class="fas fa-layer-group"></i></div>
                <div>
                    <div class="sg-stat-value">{{ $stats['total_tracked'] }}</div>
                    <div class="sg-stat-label">Domande tracciate</div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6 sg-grid-col">
            <div class="sg-stat-card">
                <div class="sg-stat-icon grad-green"><i class="fas fa-trophy"></i></div>
                <div>
                    <div class="sg-stat-value">{{ $stats['mastered'] }}</div>
                    <div class="sg-stat-label">Padroneggiata</div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6 sg-grid-col">
            <div class="sg-stat-card">
                <div class="sg-stat-icon grad-orange"><i class="fas fa-sync-alt"></i></div>
                <div>
                    <div class="sg-stat-value">{{ $stats['learning'] }}</div>
                    <div class="sg-stat-label">In apprendimento</div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6 sg-grid-col">
            <div class="sg-stat-card">
                <div class="sg-stat-icon grad-red"><i class="fas fa-star"></i></div>
                <div>
                    <div class="sg-stat-value">{{ $stats['new'] }}</div>
                    <div class="sg-stat-label">Da iniziare</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Upcoming --}}
    <div class="row sg-grid-row">
        <div class="col-md-4 sg-grid-col">
            <div class="info-box {{ $upcoming['due_today'] > 0 ? 'bg-gradient-danger' : 'bg-gradient-light' }}">
                <span class="info-box-icon"><i class="fas fa-exclamation-circle"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Oggi</span>
                    <span class="info-box-number">{{ $upcoming['due_today'] }}</span>
                    <span class="progress-description">domande in scadenza</span>
                </div>
            </div>
        </div>
        <div class="col-md-4 sg-grid-col">
            <div class="info-box bg-gradient-light">
                <span class="info-box-icon"><i class="fas fa-calendar-day"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Domani</span>
                    <span class="info-box-number">{{ $upcoming['due_tomorrow'] }}</span>
                    <span class="progress-description">domande in scadenza</span>
                </div>
            </div>
        </div>
        <div class="col-md-4 sg-grid-col">
            <div class="info-box bg-gradient-light">
                <span class="info-box-icon"><i class="fas fa-calendar-week"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Questa settimana</span>
                    <span class="info-box-number">{{ $upcoming['due_this_week'] }}</span>
                    <span class="progress-description">domande in scadenza</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Ripasso per categoria --}}
    @if($stats['total_tracked'] > 0 && $categories->isNotEmpty())
        <div class="sg-card sg-mt-3">
            <div class="sg-card-header">
                <h2 class="sg-card-header-title">Ripasso per categoria</h2>
            </div>
            <div class="sg-card-body p-3">
                <form action="{{ route('viewer.smart-review.session') }}" method="GET"
                      class="d-flex align-items-center" style="gap:1rem; flex-wrap:wrap;">
                    <div class="flex-grow-1">
                        <select name="category_id" class="form-control">
                            <option value="">Tutte le categorie</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="sg-btn sg-btn-primary">
                        <i class="fas fa-play mr-1"></i> Avvia sessione filtrata
                    </button>
                </form>
            </div>
        </div>
    @endif

    @if($stats['total_tracked'] === 0)
        <div class="sg-card sg-mt-3">
            <div class="sg-card-body text-center p-5">
                <i class="fas fa-brain text-muted fa-3x"></i>
                <h3 class="mt-3">Inizia a studiare</h3>
                <p class="text-muted">
                    Il sistema traccerà automaticamente le domande man mano che studi o svolgi quiz.<br>
                    Più rispondi, più il ripasso intelligente diventa preciso.
                </p>
                <a href="{{ route('study.index') }}" class="sg-btn sg-btn-primary mt-2">
                    <i class="fas fa-graduation-cap mr-1"></i> Modalità studio
                </a>
            </div>
        </div>
    @endif

</div>
@endsection
