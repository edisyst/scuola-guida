<x-guest-layout>
    <h1 class="sg-auth-title">Conferma password</h1>
    <p class="sg-auth-subtitle">
        {{ __('Questa è un\'area sicura dell\'applicazione. Conferma la tua password per continuare.') }}
    </p>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <div class="sg-form-group">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <x-primary-button class="sg-btn-block sg-mt-2">
            {{ __('Conferma') }}
        </x-primary-button>
    </form>
</x-guest-layout>
