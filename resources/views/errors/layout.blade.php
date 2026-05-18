<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('code') — {{ config('app.name') }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/scuola-guida.css') }}">
    @yield('theme-vars')
    <style>
        * { box-sizing: border-box; }

        html, body {
            margin: 0;
            min-height: 100vh;
        }

        body {
            background: linear-gradient(135deg, #0d0d1a 0%, #1a1a2e 50%, #0f3460 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            overflow: hidden;
            position: relative;
        }

        .bg-blob-1, .bg-blob-2 {
            position: fixed;
            border-radius: 50%;
            pointer-events: none;
            z-index: 0;
        }
        .bg-blob-1 {
            width: 600px; height: 600px;
            top: -150px; left: -150px;
            background: radial-gradient(circle, rgba(67,97,238,.2) 0%, transparent 65%);
        }
        .bg-blob-2 {
            width: 450px; height: 450px;
            bottom: -100px; right: -100px;
            background: radial-gradient(circle, rgba(var(--error-glow-rgb), .18) 0%, transparent 65%);
        }

        .error-card {
            position: relative;
            z-index: 1;
            background: rgba(255,255,255,.05);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 24px;
            padding: 3rem 3.5rem;
            text-align: center;
            max-width: 520px;
            width: 90%;
            box-shadow: 0 24px 64px rgba(0,0,0,.45), inset 0 1px 0 rgba(255,255,255,.08);
        }
        @media (max-width: 575.98px) {
            body { overflow-y: auto; align-items: flex-start; padding: 60px 0 24px; }
            .error-card { padding: 2rem 1.5rem; border-radius: 18px; }
            .btn-ghost-error { margin-left: 0; margin-top: 8px; }
        }

        .error-icon {
            width: 72px; height: 72px;
            border-radius: 20px;
            background: rgba(var(--error-glow-rgb), .15);
            border: 1px solid rgba(var(--error-glow-rgb), .3);
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.25rem;
            font-size: 1.75rem;
            color: var(--error-color);
        }

        .error-code {
            font-size: clamp(5rem, 16vw, 7.5rem);
            font-weight: 900;
            line-height: 1;
            letter-spacing: -4px;
            margin-bottom: .15rem;
            background: var(--error-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            filter: drop-shadow(0 0 32px rgba(var(--error-glow-rgb), .5));
        }

        .divider {
            width: 44px; height: 3px;
            background: var(--error-gradient);
            border-radius: 2px;
            margin: 1rem auto 1.25rem;
        }

        .error-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: .5rem;
            letter-spacing: -.2px;
        }

        .error-message {
            font-size: .9rem;
            color: rgba(255,255,255,.58);
            line-height: 1.65;
            margin-bottom: 2rem;
        }

        .btn-primary-error {
            display: inline-flex;
            align-items: center; gap: 8px;
            padding: 11px 26px;
            font-weight: 700; font-size: .875rem;
            letter-spacing: .4px;
            border-radius: 12px;
            text-decoration: none;
            color: #fff;
            background: var(--error-gradient);
            border: none;
            box-shadow: 0 4px 20px rgba(var(--error-glow-rgb), .4);
            transition: transform .2s ease, box-shadow .2s ease;
        }
        .btn-primary-error:hover {
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(var(--error-glow-rgb), .55);
        }

        .btn-ghost-error {
            display: inline-flex;
            align-items: center; gap: 6px;
            padding: 10px 20px;
            font-weight: 600; font-size: .85rem;
            border-radius: 10px;
            text-decoration: none;
            color: rgba(255,255,255,.5);
            border: 1px solid rgba(255,255,255,.14);
            background: transparent;
            transition: all .2s ease;
            margin-left: 10px;
        }
        .btn-ghost-error:hover {
            color: rgba(255,255,255,.88);
            border-color: rgba(255,255,255,.32);
            background: rgba(255,255,255,.07);
        }

        .app-brand {
            position: fixed;
            top: 22px; left: 28px;
            z-index: 10;
            text-decoration: none;
            color: rgba(255,255,255,.38);
            font-size: .88rem;
            font-weight: 700;
            letter-spacing: .3px;
            transition: color .2s;
        }
        .app-brand:hover { color: rgba(255,255,255,.75); }
        .app-brand span { color: rgba(255,255,255,.65); }

        .http-badge {
            display: inline-block;
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: rgba(var(--error-glow-rgb), 1);
            background: rgba(var(--error-glow-rgb), .12);
            border: 1px solid rgba(var(--error-glow-rgb), .25);
            border-radius: 6px;
            padding: 3px 10px;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>

    <div class="bg-blob-1"></div>
    <div class="bg-blob-2"></div>

    <a href="{{ url('/') }}" class="app-brand">
        <span>{{ config('app.name') }}</span>
    </a>

    <div class="error-card">

        <div class="error-icon">
            <i class="fas @yield('icon')"></i>
        </div>

        <div class="http-badge">HTTP @yield('code')</div>

        <div class="error-code">@yield('code')</div>

        <div class="divider"></div>

        <h1 class="error-title">@yield('title')</h1>
        <p class="error-message">@yield('message')</p>

        <div>
            <a href="{{ url('/') }}" class="btn-primary-error">
                <i class="fas fa-home"></i> Torna alla home
            </a>
            <a href="javascript:history.back()" class="btn-ghost-error">
                <i class="fas fa-arrow-left"></i> Indietro
            </a>
        </div>

    </div>

</body>
</html>
