<x-guest-layout>
    <h1 class="sg-auth-title">Verifica identità</h1>
    <p class="sg-auth-subtitle">Inserisci il codice OTP dall'app di autenticazione oppure usa un codice di recupero.</p>

    @if ($errors->has('code') || $errors->has('recovery_code'))
        <div class="alert alert-danger sg-mb-2">
            {{ $errors->first('code') ?: $errors->first('recovery_code') }}
        </div>
    @endif

    {{-- OTP form (default) --}}
    <div id="otp-form">
        <form method="POST" action="{{ route('2fa.challenge.verify') }}">
            @csrf

            <div class="sg-form-group">
                <label for="code" class="sg-form-label">Codice OTP</label>
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
            </div>

            <button type="submit" class="sg-btn sg-btn-primary sg-btn-block sg-mt-2">
                Verifica codice OTP
            </button>
        </form>

        <div class="text-center sg-mt-2">
            <a href="#" class="sg-link sg-link-muted" style="font-size:.85rem;"
               onclick="document.getElementById('otp-form').style.display='none'; document.getElementById('recovery-form').style.display='block'; return false;">
                Usa un codice di recupero
            </a>
        </div>
    </div>

    {{-- Recovery code form (hidden by default) --}}
    <div id="recovery-form" style="display:none;">
        <form method="POST" action="{{ route('2fa.challenge.verify') }}">
            @csrf

            <div class="sg-form-group">
                <label for="recovery_code" class="sg-form-label">Codice di recupero</label>
                <input id="recovery_code"
                       name="recovery_code"
                       type="text"
                       autocomplete="off"
                       class="sg-form-control @error('recovery_code') is-invalid @enderror"
                       placeholder="XXXXX-XXXXX">
            </div>

            <button type="submit" class="sg-btn sg-btn-primary sg-btn-block sg-mt-2">
                Verifica codice di recupero
            </button>
        </form>

        <div class="text-center sg-mt-2">
            <a href="#" class="sg-link sg-link-muted" style="font-size:.85rem;"
               onclick="document.getElementById('recovery-form').style.display='none'; document.getElementById('otp-form').style.display='block'; return false;">
                Torna al codice OTP
            </a>
        </div>
    </div>

    @if(session('warning'))
        <div class="alert alert-warning sg-mt-2">{{ session('warning') }}</div>
    @endif
</x-guest-layout>
