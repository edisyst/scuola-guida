@extends('layouts.guest')

@section('content')

@php $carouselImages = json_decode(setting('school.carousel_images', '[]'), true) ?? []; @endphp

{{-- ============================================================
     Sezione 1 — Hero arioso senza annidamento a box (15.3)
     ============================================================ --}}
<section class="sg-hero">

    {{-- Sfondo: carosello o accent solido via CSS --}}
    <div class="sg-hero-bg">
        @if(count($carouselImages) > 0)
            <div id="homepageCarousel"
                 class="carousel slide carousel-fade"
                 data-bs-ride="carousel">
                <div class="carousel-inner">
                    @foreach($carouselImages as $i => $imgPath)
                        <div class="carousel-item {{ $i === 0 ? 'active' : '' }}">
                            <img src="{{ Storage::url($imgPath) }}"
                                 class="d-block w-100"
                                 alt="">
                        </div>
                    @endforeach
                </div>
                @if(count($carouselImages) > 1)
                    <button class="carousel-control-prev" type="button"
                            data-bs-target="#homepageCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon"></span>
                    </button>
                    <button class="carousel-control-next" type="button"
                            data-bs-target="#homepageCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon"></span>
                    </button>
                @endif
            </div>
        @endif
    </div>

    {{-- Overlay gradiente per leggibilità WCAG --}}
    <div class="sg-hero-overlay" aria-hidden="true"></div>

    {{-- Contenuto direttamente sul bg, nessun box contenitore annidato --}}
    <div class="sg-hero-content">

        {{-- Logo o icona discreta --}}
        @if(setting('school.logo_path'))
            <img src="{{ Storage::url(setting('school.logo_path')) }}"
                 alt="{{ setting('school.name', config('app.name')) }}"
                 class="sg-hero-logo">
        @else
            <span class="sg-hero-icon"><i class="fas fa-car"></i></span>
        @endif

        <h1 class="sg-hero-title">
            {{ setting('school.name', config('app.name')) }}
        </h1>

        <p class="sg-hero-tagline">
            {{ setting('school.tagline', __('guest.hero_tagline_default')) }}
        </p>

        <div class="sg-hero-actions">
            @if(Route::has('register'))
                <a href="{{ route('register') }}" class="sg-btn-hero-primary">
                    <i class="fas fa-rocket me-2"></i>{{ __('guest.cta_start') }}
                </a>
            @endif
            <a href="{{ route('login') }}" class="sg-btn-hero-secondary">
                {{ __('guest.cta_login') }}
            </a>
        </div>

    </div>
</section>

{{-- ============================================================
     Sezione 2 — Statistiche (solo se almeno uno > 0)
     ============================================================ --}}
@if($stats['quiz_count'] > 0 || $stats['question_count'] > 0 || $stats['license_types_count'] > 0)
<section class="py-5">
    <div class="container">
        <div class="row g-4 justify-content-center">
            @if($stats['quiz_count'] > 0)
            <div class="col-md-4">
                <div class="sg-guest-stat-card text-center">
                    <i class="fas fa-book fa-2x mb-3" style="color:var(--sg-accent);"></i>
                    <div class="sg-guest-stat-value">{{ number_format($stats['quiz_count']) }}</div>
                    <div class="sg-guest-stat-label">{{ __('guest.stat_quiz') }}</div>
                </div>
            </div>
            @endif
            @if($stats['question_count'] > 0)
            <div class="col-md-4">
                <div class="sg-guest-stat-card text-center">
                    <i class="fas fa-question-circle fa-2x mb-3" style="color:var(--sg-success);"></i>
                    <div class="sg-guest-stat-value">{{ number_format($stats['question_count']) }}</div>
                    <div class="sg-guest-stat-label">{{ __('guest.stat_questions') }}</div>
                </div>
            </div>
            @endif
            @if($stats['license_types_count'] > 0)
            <div class="col-md-4">
                <div class="sg-guest-stat-card text-center">
                    <i class="fas fa-id-card fa-2x mb-3" style="color:var(--sg-warning);"></i>
                    <div class="sg-guest-stat-value">{{ $stats['license_types_count'] }}</div>
                    <div class="sg-guest-stat-label">{{ __('guest.stat_license_types') }}</div>
                </div>
            </div>
            @endif
        </div>
    </div>
</section>
@endif

{{-- ============================================================
     Sezione 3 — Feature highlights
     ============================================================ --}}
<section class="py-5 sg-features-section">
    <div class="container">
        <h2 class="text-center mb-5 sg-section-heading">
            {{ __('guest.features_title', ['name' => setting('school.name', config('app.name'))]) }}
        </h2>
        <div class="row g-4">
            <div class="col-sm-6 col-md-3">
                <div class="sg-feature-card">
                    <div class="sg-feature-icon sg-feature-icon--blue">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <h3 class="sg-feature-title">{{ __('guest.feature_quiz_title') }}</h3>
                    <p class="sg-feature-text">{{ __('guest.feature_quiz_text') }}</p>
                </div>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="sg-feature-card">
                    <div class="sg-feature-icon sg-feature-icon--red">
                        <i class="fas fa-stopwatch"></i>
                    </div>
                    <h3 class="sg-feature-title">{{ __('guest.feature_simulator_title') }}</h3>
                    <p class="sg-feature-text">{{ __('guest.feature_simulator_text') }}</p>
                </div>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="sg-feature-card">
                    <div class="sg-feature-icon sg-feature-icon--green">
                        <i class="fas fa-car"></i>
                    </div>
                    <h3 class="sg-feature-title">{{ __('guest.feature_driving_title') }}</h3>
                    <p class="sg-feature-text">{{ __('guest.feature_driving_text') }}</p>
                </div>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="sg-feature-card">
                    <div class="sg-feature-icon sg-feature-icon--teal">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3 class="sg-feature-title">{{ __('guest.feature_progress_title') }}</h3>
                    <p class="sg-feature-text">{{ __('guest.feature_progress_text') }}</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ============================================================
     Sezione 4 — Tipi di patente (solo se > 1)
     ============================================================ --}}
@if($licenseTypes->count() > 1)
<section class="py-5">
    <div class="container text-center">
        <h2 class="sg-section-heading mb-4">{{ __('guest.license_types_title') }}</h2>
        <div>
            @foreach($licenseTypes as $type)
                <span class="sg-badge-license">{{ $type->name }}</span>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ============================================================
     Sezione 5 — CTA finale
     ============================================================ --}}
<section class="sg-cta-section">
    <div class="container">
        <h2 class="sg-cta-title">{{ __('guest.final_cta_title') }}</h2>
        <p class="sg-cta-subtitle">{{ __('guest.final_cta_subtitle') }}</p>
        @if(Route::has('register'))
            <a href="{{ route('register') }}" class="sg-btn-cta">
                <i class="fas fa-user-plus me-2"></i>{{ __('guest.final_cta_button') }}
            </a>
        @endif
    </div>
</section>

@endsection
