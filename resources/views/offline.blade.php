<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#4361ee">
    <title>Sei offline — ScuolaGUIDA</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8f9fb;
            color: #212529;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            text-align: center;
        }

        .icon-wrap {
            width: 96px;
            height: 96px;
            background: #e9ecef;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }

        .icon-wrap svg {
            width: 48px;
            height: 48px;
            color: #6c757d;
            fill: none;
            stroke: currentColor;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        h1 {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: .75rem;
            color: #1a1a2e;
        }

        p {
            font-size: 1rem;
            color: #6c757d;
            max-width: 360px;
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .btn-group {
            display: flex;
            flex-direction: column;
            gap: .75rem;
            width: 100%;
            max-width: 280px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            padding: .75rem 1.5rem;
            border-radius: .5rem;
            font-size: .95rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            text-decoration: none;
            transition: opacity .15s;
        }

        .btn:hover { opacity: .88; }

        .btn-primary {
            background: #4361ee;
            color: #fff;
        }

        .btn-outline {
            background: transparent;
            color: #4361ee;
            border: 2px solid #4361ee;
        }

        .brand {
            margin-top: 3rem;
            font-size: .85rem;
            color: #adb5bd;
        }

        .brand span { font-weight: 700; }
    </style>
</head>
<body>

    <div class="icon-wrap">
        <svg viewBox="0 0 24 24" aria-hidden="true">
            <!-- WiFi off icon (Feather icons style) -->
            <line x1="1" y1="1" x2="23" y2="23"/>
            <path d="M16.72 11.06A10.94 10.94 0 0 1 19 12.55"/>
            <path d="M5 12.55a10.94 10.94 0 0 1 5.17-2.39"/>
            <path d="M10.71 5.05A16 16 0 0 1 22.56 9"/>
            <path d="M1.42 9a15.91 15.91 0 0 1 4.7-2.88"/>
            <path d="M8.53 16.11a6 6 0 0 1 6.95 0"/>
            <circle cx="12" cy="20" r="1" fill="currentColor" stroke="none"/>
        </svg>
    </div>

    <h1>Sei offline</h1>

    <p>
        Alcune funzioni sono limitate senza connessione.<br>
        Puoi continuare a studiare in modalità studio se l'hai già aperta in precedenza.
    </p>

    <div class="btn-group">
        <button class="btn btn-primary" onclick="location.reload()">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/>
                <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>
            </svg>
            Riprova
        </button>

        <a href="/study" class="btn btn-outline">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/>
                <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
            </svg>
            Vai alla modalità studio
        </a>
    </div>

    <p class="brand"><span>Scuola</span>GUIDA</p>

</body>
</html>
