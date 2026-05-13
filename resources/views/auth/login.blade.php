<x-guest-layout>
    <h1 class="sg-auth-title">Bentornato</h1>
    <p class="sg-auth-subtitle">Accedi al tuo account per continuare a esercitarti.</p>

    <x-auth-session-status class="sg-mb-2" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="sg-form-group">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div class="sg-form-group">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div class="sg-form-group sg-flex-between">
            <label for="remember_me" class="sg-form-check">
                <input id="remember_me" type="checkbox" name="remember">
                <span>{{ __('Ricordami') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a class="sg-link sg-link-muted" style="font-size:.85rem;" href="{{ route('password.request') }}">
                    {{ __('Password dimenticata?') }}
                </a>
            @endif
        </div>

        <x-primary-button class="sg-btn-block sg-mt-2">
            {{ __('Accedi') }}
        </x-primary-button>
    </form>

    @if (Route::has('register'))
        <div class="sg-auth-footer">
            Non hai un account?
            <a href="{{ route('register') }}" class="sg-link">{{ __('Registrati') }}</a>
        </div>
    @endif
</x-guest-layout>
