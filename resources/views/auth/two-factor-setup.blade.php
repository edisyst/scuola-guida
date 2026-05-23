<x-guest-layout>
    <h1 class="sg-auth-title">Configura il 2FA</h1>
    <p class="sg-auth-subtitle">
        Scansiona il QR code con Google Authenticator, Authy o un'app compatibile TOTP.
        Poi inserisci il codice OTP per verificare la configurazione.
    </p>

    @if ($errors->any())
        <div class="alert alert-danger sg-mb-2">
            {{ $errors->first() }}
        </div>
    @endif

    {{-- QR code --}}
    <div class="text-center sg-mb-2">
        {!! $qrSvg !!}
    </div>

    {{-- Secret key (inserimento manuale) --}}
    <div class="sg-mb-2">
        <p class="sg-text-muted" style="font-size:.85rem; margin-bottom:.25rem;">
            Inserimento manuale — chiave segreta:
        </p>
        <code style="display:block; word-break:break-all; font-size:.9rem; background:#f4f4f4; padding:.5rem; border-radius:4px;">
            {{ $secret }}
        </code>
    </div>

    {{-- OTP verification form --}}
    <form method="POST" action="{{ route('2fa.setup.store') }}">
        @csrf

        <div class="sg-form-group">
            <label for="code" class="sg-form-label">Codice OTP (6 cifre)</label>
            <input id="code"
                   name="code"
                   type="text"
                   inputmode="numeric"
                   pattern="[0-9]{6}"
                   maxlength="6"
                   autocomplete="one-time-code"
                   autofocus
                   class="sg-form-control @error('code') is-invalid @enderror"
                   placeholder="000000">
            @error('code')
                <div class="sg-form-error">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="sg-btn sg-btn-primary sg-btn-block sg-mt-2">
            Verifica e attiva 2FA
        </button>
    </form>

    @if(session('warning'))
        <div class="alert alert-warning sg-mt-2">{{ session('warning') }}</div>
    @endif
</x-guest-layout>
