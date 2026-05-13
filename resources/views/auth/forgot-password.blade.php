<x-guest-layout>
    <h1 class="sg-auth-title">Password dimenticata</h1>
    <p class="sg-auth-subtitle">
        {{ __('Inserisci la tua email: ti invieremo un link per impostare una nuova password.') }}
    </p>

    <x-auth-session-status class="sg-mb-2" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="sg-form-group">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <x-primary-button class="sg-btn-block sg-mt-2">
            {{ __('Invia link di reset') }}
        </x-primary-button>
    </form>

    <div class="sg-auth-footer">
        <a href="{{ route('login') }}" class="sg-link">{{ __('Torna al login') }}</a>
    </div>
</x-guest-layout>
