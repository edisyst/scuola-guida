<x-guest-layout>
    <h1 class="sg-auth-title">Crea un account</h1>
    <p class="sg-auth-subtitle">Inizia subito ad allenarti per l'esame di teoria.</p>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div class="sg-form-group">
            <x-input-label for="name" :value="__('Nome')" />
            <x-text-input id="name" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" />
        </div>

        <div class="sg-form-group">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div class="sg-form-group">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div class="sg-form-group">
            <x-input-label for="password_confirmation" :value="__('Conferma Password')" />
            <x-text-input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" />
        </div>

        @foreach ($extraFields ?? [] as $field)
        <div class="sg-form-group">
            <x-input-label for="{{ $field['key'] }}" :value="__($field['label_key']) . ($field['required'] ? ' *' : '')" />
            <x-text-input
                id="{{ $field['key'] }}"
                type="{{ $field['type'] === 'date' ? 'date' : ($field['type'] === 'tel' ? 'tel' : 'text') }}"
                name="{{ $field['key'] }}"
                :value="old($field['key'])"
                :required="$field['required']"
                autocomplete="{{ $field['key'] }}" />
            <x-input-error :messages="$errors->get($field['key'])" />
        </div>
        @endforeach

        <x-primary-button class="sg-btn-block sg-mt-2">
            {{ __('Registrati') }}
        </x-primary-button>
    </form>

    <div class="sg-auth-footer">
        Hai già un account?
        <a href="{{ route('login') }}" class="sg-link">{{ __('Accedi') }}</a>
    </div>
</x-guest-layout>
