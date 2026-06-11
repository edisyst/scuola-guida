<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Scuola-Guida') }}</title>
        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
        <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <link rel="stylesheet" href="{{ asset('css/scuola-guida.css') }}">
    </head>
    <body class="font-sans antialiased">
        <div class="sg-auth-shell">
            <div class="sg-auth-card">
                <div class="sg-auth-brand">
                    <a href="/" class="sg-auth-logo" title="{{ config('app.name', 'Scuola-Guida') }}">
                        <i class="fas fa-car"></i>
                    </a>
                    <span class="sg-auth-app-name">{{ config('app.name', 'Scuola Guida') }}</span>
                </div>

                {{ $slot }}
            </div>
        </div>
    </body>
</html>
