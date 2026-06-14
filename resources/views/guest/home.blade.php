@extends('layouts.guest')

@section('content')

{{-- ============================================================
     Sezione 1 — Hero con carosello come sfondo
     ============================================================ --}}
@php $carouselImages = json_decode(setting('school.carousel_images', '[]'), true) ?? []; @endphp
<section class="position-relative d-flex align-items-center text-white overflow-hidden"
         style="min-height:60vh;">

    {{-- Sfondo: carosello se ci sono immagini, altrimenti colore accent --}}
    @if(count($carouselImages) > 0)
        <div id="homepageCarousel"
             class="carousel slide carousel-fade position-absolute top-0 start-0 w-100 h-100"
             style="z-index:0;"
             data-bs-ride="carousel">
            <div class="carousel-inner h-100">
                @foreach($carouselImages as $i => $imgPath)
                    <div class="carousel-item h-100 {{ $i === 0 ? 'active' : '' }}">
                        <img src="{{ Storage::url($imgPath) }}"
                             class="d-block w-100 h-100"
                             style="object-fit:cover;"
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
        {{-- overlay scuro per leggibilità testo --}}
        <div class="position-absolute top-0 start-0 w-100 h-100"
             style="background:rgba(0,0,0,0.45);z-index:1;"></div>
    @else
        <div class="position-absolute top-0 start-0 w-100 h-100"
             style="background-color:var(--sg-accent);z-index:0;"></div>
    @endif

    {{-- Contenuto hero --}}
    <div class="container text-center py-5 position-relative" style="z-index:2;">
        @if(setting('school.logo_path'))
            <img src="{{ Storage::url(setting('school.logo_path')) }}"
                 alt="{{ setting('school.name', config('app.name')) }}"
                 class="mb-4"
                 style="max-height:96px;width:auto;">
        @else
            <div class="mb-4">
                <i class="fas fa-car fa-4x opacity-75"></i>
            </div>
        @endif

        <h1 class="display-5 fw-bold">
            {{ setting('school.name', config('app.name')) }}
        </h1>

        <p class="lead mb-4">
            {{ setting('school.tagline', __('guest.hero_tagline_default')) }}
        </p>

        <div class="d-flex flex-wrap justify-content-center gap-3">
            @if(Route::has('register'))
                <a href="{{ route('register') }}"
                   class="btn btn-light btn-lg fw-semibold px-4">
                    <i class="fas fa-rocket me-2"></i>{{ __('guest.cta_start') }}
                </a>
            @endif
            <a href="{{ route('login') }}"
               class="btn btn-outline-light btn-lg px-4">
                {{ __('guest.cta_login') }}
            </a>
        </div>
    </div>
</section>

{{-- ============================================================
     Sezione 2 — Statistiche (solo se almeno uno > 0)
     ============================================================ --}}
@if($stats['quiz_count'] > 0 || $stats['question_count'] > 0 || $stats['license_types_count'] > 0)
<section class="py-5" class="bg-white">
    <div class="container">
        <div class="row g-4 justify-content-center">
            @if($stats['quiz_count'] > 0)
            <div class="col-md-4">
                <div class="card text-center h-100 border-0 shadow-sm"
                     class="">
                    <div class="card-body py-4">
                        <i class="fas fa-book fa-2x mb-3 text-primary"></i>
                        <div class="display-6 fw-bold">{{ number_format($stats['quiz_count']) }}</div>
                        <div class="text-muted">{{ __('guest.stat_quiz') }}</div>
                    </div>
                </div>
            </div>
            @endif
            @if($stats['question_count'] > 0)
            <div class="col-md-4">
                <div class="card text-center h-100 border-0 shadow-sm"
                     class="">
                    <div class="card-body py-4">
                        <i class="fas fa-question-circle fa-2x mb-3 text-success"></i>
                        <div class="display-6 fw-bold">{{ number_format($stats['question_count']) }}</div>
                        <div class="text-muted">{{ __('guest.stat_questions') }}</div>
                    </div>
                </div>
            </div>
            @endif
            @if($stats['license_types_count'] > 0)
            <div class="col-md-4">
                <div class="card text-center h-100 border-0 shadow-sm"
                     class="">
                    <div class="card-body py-4">
                        <i class="fas fa-id-card fa-2x mb-3 text-warning"></i>
                        <div class="display-6 fw-bold">{{ $stats['license_types_count'] }}</div>
                        <div class="text-muted">{{ __('guest.stat_license_types') }}</div>
                    </div>
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
<section class="py-5" class="bg-light">
    <div class="container">
        <h2 class="text-center mb-5">
            {{ __('guest.features_title', ['name' => setting('school.name', config('app.name'))]) }}
        </h2>
        <div class="row g-4">
            <div class="col-sm-6 col-md-3">
                <div class="card h-100 border-0 shadow-sm text-center"
                     class="">
                    <div class="card-body py-4">
                        <i class="fas fa-graduation-cap fa-2x text-primary mb-3"></i>
                        <h5 class="card-title">{{ __('guest.feature_quiz_title') }}</h5>
                        <p class="card-text small text-muted">{{ __('guest.feature_quiz_text') }}</p>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="card h-100 border-0 shadow-sm text-center"
                     class="">
                    <div class="card-body py-4">
                        <i class="fas fa-stopwatch fa-2x text-danger mb-3"></i>
                        <h5 class="card-title">{{ __('guest.feature_simulator_title') }}</h5>
                        <p class="card-text small text-muted">{{ __('guest.feature_simulator_text') }}</p>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="card h-100 border-0 shadow-sm text-center"
                     class="">
                    <div class="card-body py-4">
                        <i class="fas fa-car fa-2x text-success mb-3"></i>
                        <h5 class="card-title">{{ __('guest.feature_driving_title') }}</h5>
                        <p class="card-text small text-muted">{{ __('guest.feature_driving_text') }}</p>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="card h-100 border-0 shadow-sm text-center"
                     class="">
                    <div class="card-body py-4">
                        <i class="fas fa-chart-line fa-2x text-info mb-3"></i>
                        <h5 class="card-title">{{ __('guest.feature_progress_title') }}</h5>
                        <p class="card-text small text-muted">{{ __('guest.feature_progress_text') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ============================================================
     Sezione 4 — Tipi di patente (solo se > 1)
     ============================================================ --}}
@if($licenseTypes->count() > 1)
<section class="py-5" class="bg-white">
    <div class="container text-center">
        <h2 class="mb-4">{{ __('guest.license_types_title') }}</h2>
        <div>
            @foreach($licenseTypes as $type)
                <span class="badge bg-secondary m-1 fs-6">{{ $type->name }}</span>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ============================================================
     Sezione 5 — CTA finale
     ============================================================ --}}
<section class="py-5 text-center" class="bg-light">
    <div class="container">
        <h2 class="mb-3">{{ __('guest.final_cta_title') }}</h2>
        <p class="lead text-muted mb-4">{{ __('guest.final_cta_subtitle') }}</p>
        @if(Route::has('register'))
            <a href="{{ route('register') }}"
               class="btn btn-lg text-white px-5"
               style="background-color:var(--sg-accent);">
                <i class="fas fa-user-plus me-2"></i>{{ __('guest.final_cta_button') }}
            </a>
        @endif
    </div>
</section>

@endsection
