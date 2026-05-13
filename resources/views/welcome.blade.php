<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name', 'Scuola Guida') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/scuola-guida.css') }}">

    <style>
        body {
            margin: 0;
            font-family: 'Figtree', system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
            -webkit-font-smoothing: antialiased;
        }
    </style>
</head>
<body>
    <div class="sg-home">
        <nav class="sg-home-nav">
            <a href="/" class="sg-home-brand">
                <span class="sg-home-brand-icon"><i class="fas fa-car"></i></span>
                {{ config('app.name', 'Scuola Guida') }}
            </a>

            @if (Route::has('login'))
                <div class="sg-home-actions">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="sg-btn sg-btn-cta sg-btn-sm">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="sg-btn sg-btn-ghost sg-btn-sm">
                            Accedi
                        </a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="sg-btn sg-btn-cta sg-btn-sm">
                                Registrati
                            </a>
                        @endif
                    @endauth
                </div>
            @endif
        </nav>

        <section class="sg-home-hero">
            <span class="sg-home-eyebrow">Preparati all'esame di teoria</span>
            <h1 class="sg-home-title">
                La piattaforma di quiz per la patente,<br>semplice e intelligente.
            </h1>
            <p class="sg-home-lead">
                Studia con quiz aggiornati, monitora i tuoi progressi e simula l'esame reale
                con il timer e il limite di errori ufficiale.
            </p>
            <div class="sg-home-cta">
                @auth
                    <a href="{{ url('/dashboard') }}" class="sg-btn sg-btn-cta">
                        <i class="fas fa-play"></i> Vai alla dashboard
                    </a>
                @else
                    <a href="{{ route('register') }}" class="sg-btn sg-btn-cta">
                        <i class="fas fa-rocket"></i> Inizia gratis
                    </a>
                    <a href="{{ route('login') }}" class="sg-btn sg-btn-ghost">
                        Ho già un account
                    </a>
                @endauth
            </div>
        </section>

        <section class="sg-home-features">
            <div class="sg-feature-grid">
                <div class="sg-feature-card">
                    <div class="sg-feature-icon"><i class="fas fa-stopwatch"></i></div>
                    <h3>Simulazione realistica</h3>
                    <p>30 minuti, 30 domande, massimo 3 errori. Esattamente come l'esame ufficiale.</p>
                </div>
                <div class="sg-feature-card">
                    <div class="sg-feature-icon alt-blue"><i class="fas fa-chart-line"></i></div>
                    <h3>Statistiche dettagliate</h3>
                    <p>Tieni traccia di ogni tentativo, identifica le tue lacune e migliora dove serve.</p>
                </div>
                <div class="sg-feature-card">
                    <div class="sg-feature-icon alt-orange"><i class="fas fa-bookmark"></i></div>
                    <h3>Domande per categoria</h3>
                    <p>Allenati su capitoli specifici o lascia che il sistema generi quiz casuali per te.</p>
                </div>
            </div>
        </section>

        <footer class="sg-home-footer">
            &copy; {{ date('Y') }} {{ config('app.name', 'Scuola Guida') }} — Tutti i diritti riservati
        </footer>
    </div>
</body>
</html>
