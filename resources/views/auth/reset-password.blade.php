<x-guest-layout>
    <h1 class="sg-auth-title">Reimposta password</h1>
    <p class="sg-auth-subtitle">Scegli una nuova password per accedere al tuo account.</p>

    <form method="POST" action="{{ route('password.store') }}">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="sg-form-group">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div class="sg-form-group">
            <x-input-label for="password" :value="__('Nuova password')" />
            <x-text-input id="password" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div class="sg-form-group">
            <x-input-label for="password_confirmation" :value="__('Conferma password')" />
            <x-text-input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" />
        </div>

        <x-primary-button class="sg-btn-block sg-mt-2">
            {{ __('Reimposta password') }}
        </x-primary-button>
    </form>
</x-guest-layout>
