@extends('adminlte::page')

@section('title', $title ?? 'Admin')

@section('css')
    {{-- PWA --}}
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#4361ee">
    <meta name="vapid-public-key" content="{{ config('webpush.vapid.public_key') }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="ScuolaGUIDA">
    <link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon.png') }}">

    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <link rel="stylesheet" href="{{ asset('css/scuola-guida.css') }}">
    @include('layouts.partials.appearance-css')
    <style>
        .navbar-brand img.school-logo {
            max-height: 40px;
            width: auto;
        }
    </style>

    {{-- DataTables i18n strings injected for current locale --}}
    @php
        $dtI18nJson = htmlspecialchars(json_encode([
            'search'            => __('datatables.search'),
            'length_menu'       => __('datatables.length_menu'),
            'info'              => __('datatables.info'),
            'info_empty'        => __('datatables.info_empty'),
            'info_filtered'     => __('datatables.info_filtered'),
            'zero_records'      => __('datatables.zero_records'),
            'loading_records'   => __('datatables.loading_records'),
            'processing'        => __('datatables.processing'),
            'paginate_first'    => __('datatables.paginate_first'),
            'paginate_last'     => __('datatables.paginate_last'),
            'paginate_previous' => __('datatables.paginate_previous'),
            'paginate_next'     => __('datatables.paginate_next'),
        ]), ENT_QUOTES, 'UTF-8');
    @endphp
    <meta name="datatables-i18n" content="{{ $dtI18nJson }}">

    @livewireStyles
@stop

@section('brand_top')
    @if(setting('school.logo_path'))
        <img src="{{ Storage::url(setting('school.logo_path')) }}"
             alt="{{ setting('school.name', config('app.name')) }}"
             class="school-logo brand-image elevation-3">
    @else
        <b>{{ setting('school.name', 'Scuola') }}</b>GUIDA
    @endif
@stop

@section('content_top_nav_right')
    {{-- Language switcher --}}
    @if(feature('exam_translations_enabled'))
    <li class="nav-item dropdown">
        <a class="nav-link" data-toggle="dropdown" href="#" aria-label="{{ config('locales.supported')[App::getLocale()]['label'] }}">
            <img src="{{ asset('images/language_flags/' . config('locales.supported')[App::getLocale()]['flag']) }}"
                 alt="{{ config('locales.supported')[App::getLocale()]['label'] }}"
                 width="20" height="14"
                 style="border-radius: 2px;">
        </a>
        <div class="dropdown-menu dropdown-menu-right">
            @foreach(config('locales.supported') as $code => $lang)
                <form method="POST" action="{{ route('locale.switch') }}">
                    @csrf
                    <input type="hidden" name="locale" value="{{ $code }}">
                    <button type="submit"
                            class="dropdown-item {{ App::getLocale() === $code ? 'active' : '' }}">
                        <img src="{{ asset('images/language_flags/' . $lang['flag']) }}"
                             alt="{{ $lang['label'] }}"
                             width="20" height="14"
                             style="border-radius: 2px; margin-right: 6px;">
                        {{ $lang['label'] }}
                    </button>
                </form>
            @endforeach
        </div>
    </li>
    @endif
    @if(auth()->check() && auth()->user()->isViewer() && auth()->user()->getActiveLicenseType())
        <li class="nav-item">
            <a href="{{ route('profile.edit') }}" class="nav-link" title="{{ __('profile.license_type_title') }}">
                <span class="badge badge-info">
                    <i class="fas fa-id-card mr-1"></i>{{ auth()->user()->getActiveLicenseType()->code }}
                </span>
            </a>
        </li>
    @endif
    <livewire:notification-bell />
@stop

@section('content_header')
    <h1>{{ $header ?? 'Admin Dashboard' }}</h1>
@stop

@section('content')
    @yield('content')
@stop

@section('js')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="{{ asset('js/datatables-i18n.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

    <script>
        $(function () {
            // Apply DataTables i18n globally for this locale
            if (typeof $.fn.dataTable !== 'undefined' && typeof window.DataTablesI18n !== 'undefined') {
                $.extend($.fn.dataTable.defaults, { language: window.DataTablesI18n.get() });
            }


            // Bootstrap switch
            $('input[data-bootstrap-switch]').each(function(){
                $(this).bootstrapSwitch();
            });

            // Config toastr
            toastr.options = {
                closeButton: true,
                progressBar: true,
                positionClass: "toast-top-right",
                timeOut: "3000"
            };

            // 🔥 NOTIFICHE SESSIONE LARAVEL
            @if(session('success'))
                toastr.success(@json(session('success')));
            @endif

            @if(session('error'))
                toastr.error(@json(session('error')));
            @endif

            @if(session('info'))
                toastr.info(@json(session('info')));
            @endif

            @if(session('warning'))
                toastr.warning(@json(session('warning')));
            @endif

        });

        // Intercettiamo il submit della ricerca navbar: apriamo i risultati
        // in una nuova scheda e costruiamo l'URL noi (il form di AdminLTE
        // include _token nel GET e l'action potrebbe non essere impostata).
        $(document).on('submit', '.navbar-search-block form', function (e) {
            e.preventDefault();
            var q = $(this).find('input[type="search"]').val().trim();
            if (q) {
                window.open('{{ route("search") }}?q=' + encodeURIComponent(q), '_blank');
            }
        });
    </script>
    @livewireScripts
    @vite(['resources/js/app.js'])
@stop
