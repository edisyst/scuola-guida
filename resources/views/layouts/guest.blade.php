<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ setting('school.name', config('app.name')) }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">

    <meta name="description"
          content="{{ setting('school.tagline', __('guest.hero_tagline_default')) }}">
    <meta property="og:title"
          content="{{ setting('school.name', config('app.name')) }}">
    <meta property="og:description"
          content="{{ setting('school.tagline', '') }}">
    @if(setting('school.logo_path'))
        <meta property="og:image"
              content="{{ Storage::url(setting('school.logo_path')) }}">
    @endif

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/scuola-guida.css') }}">

    <style>
        :root {
            --sg-accent: {{ setting('appearance.accent_color', '#3c8dbc') }};
        }
    </style>
</head>
<body class="guest-page">

    {{-- Navbar minimale --}}
    <nav class="navbar navbar-expand-md sticky-top navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="{{ route('guest.home') }}">
                @if(setting('school.logo_path'))
                    <img src="{{ Storage::url(setting('school.logo_path')) }}"
                         alt="{{ setting('school.name', config('app.name')) }}"
                         style="max-height:36px;width:auto;">
                @else
                    <i class="fas fa-car text-primary"></i>
                @endif
                <strong>{{ setting('school.name', config('app.name')) }}</strong>
            </a>

            <div class="d-flex gap-2 ms-auto">
                <a href="{{ route('login') }}" class="btn btn-outline-secondary btn-sm">
                    {{ __('guest.nav_login') }}
                </a>
                @if(Route::has('register'))
                    <a href="{{ route('register') }}" class="btn btn-sm text-white"
                       style="background-color:var(--sg-accent);">
                        {{ __('guest.nav_register') }}
                    </a>
                @endif
            </div>
        </div>
    </nav>

    @yield('content')

    {{-- Footer --}}
    <footer class="py-4 mt-5 bg-light text-muted">
        <div class="container text-center small">
            @if(setting('school.name'))
                <div class="fw-semibold mb-1">{{ setting('school.name') }}</div>
            @endif
            <div class="d-flex flex-wrap justify-content-center gap-3">
                @if(setting('school.address'))
                    <span><i class="fas fa-map-marker-alt me-1"></i>{{ setting('school.address') }}</span>
                @endif
                @if(setting('school.phone'))
                    <span><i class="fas fa-phone me-1"></i>{{ setting('school.phone') }}</span>
                @endif
                @if(setting('school.email'))
                    <span><i class="fas fa-envelope me-1"></i>{{ setting('school.email') }}</span>
                @endif
            </div>
            <div class="mt-2">&copy; {{ now()->year }} {{ setting('school.name', config('app.name')) }}</div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html>
