<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data="{
          darkMode: localStorage.getItem('sg-dark') === '1' ||
                    (localStorage.getItem('sg-dark') === null &&
                     window.matchMedia('(prefers-color-scheme: dark)').matches),
          toggleDark() {
              this.darkMode = !this.darkMode;
              localStorage.setItem('sg-dark', this.darkMode ? '1' : '0');
          }
      }"
      :data-bs-theme="darkMode ? 'dark' : 'light'">
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

    {{-- Previene il flash di tema sbagliato prima che Alpine si inizializzi --}}
    <script>
        (function(){
            var d = localStorage.getItem('sg-dark');
            if(d === '1' || (d === null && window.matchMedia('(prefers-color-scheme: dark)').matches)){
                document.documentElement.setAttribute('data-bs-theme','dark');
            }
        })();
    </script>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/scuola-guida.css') }}">

    @include('layouts.partials.appearance-css')
</head>
<body class="guest-page" :style="darkMode ? 'background:#2b3035;' : 'background:#f4f6f9;'">

    {{-- Navbar minimale --}}
    <nav class="navbar navbar-expand-md sticky-top shadow-sm"
         :class="darkMode ? 'navbar-dark bg-dark' : 'navbar-light bg-white'">
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

            <div class="d-flex gap-2 ms-auto align-items-center">

                {{-- Dark mode toggle --}}
                <button @click="toggleDark()"
                        class="btn btn-sm btn-outline-secondary"
                        :title="darkMode ? '{{ __('auth.theme_light') }}' : '{{ __('auth.theme_dark') }}'">
                    <i :class="darkMode ? 'fas fa-sun' : 'fas fa-moon'"></i>
                </button>

                {{-- Language switcher --}}
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle d-flex align-items-center gap-1"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                            title="{{ __('auth.language') }}">
                        <img src="{{ asset('images/language_flags/' . config('locales.supported')[App::getLocale()]['flag']) }}"
                             alt="{{ config('locales.supported')[App::getLocale()]['label'] }}"
                             width="18" height="13"
                             style="border-radius:2px;">
                        <span class="d-none d-md-inline">{{ strtoupper(App::getLocale()) }}</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        @foreach(config('locales.supported') as $code => $lang)
                        <li>
                            <form method="POST" action="{{ route('locale.switch') }}">
                                @csrf
                                <input type="hidden" name="locale" value="{{ $code }}">
                                <button type="submit"
                                        class="dropdown-item d-flex align-items-center gap-2 {{ App::getLocale() === $code ? 'active' : '' }}">
                                    <img src="{{ asset('images/language_flags/' . $lang['flag']) }}"
                                         alt="{{ $lang['label'] }}"
                                         width="18" height="13"
                                         style="border-radius:2px;">
                                    {{ $lang['label'] }}
                                </button>
                            </form>
                        </li>
                        @endforeach
                    </ul>
                </div>

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
    <footer class="py-4 mt-5 text-muted" :class="darkMode ? 'bg-dark' : 'bg-light'">
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
