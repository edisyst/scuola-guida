<x-guest-layout>
    <h1 class="sg-auth-title">Verifica email</h1>
    <p class="sg-auth-subtitle">
        {{ __('Grazie per esserti registrato! Prima di iniziare, verifica il tuo indirizzo email cliccando sul link che ti abbiamo inviato. Se non hai ricevuto l\'email, possiamo inviarne un\'altra.') }}
    </p>

    @if (session('status') == 'verification-link-sent')
        <div class="sg-text-success sg-mb-2" style="font-size:.88rem;font-weight:600;">
            {{ __('Un nuovo link di verifica è stato inviato al tuo indirizzo email.') }}
        </div>
    @endif

    <div class="sg-flex-between sg-mt-3" style="flex-wrap:wrap;gap:12px;">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-primary-button>
                {{ __('Reinvia email di verifica') }}
            </x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="sg-link sg-link-muted" style="font-size:.85rem;background:none;border:none;cursor:pointer;">
                {{ __('Esci') }}
            </button>
        </form>
    </div>
</x-guest-layout>
