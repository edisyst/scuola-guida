<x-guest-layout>
    <h1 class="sg-auth-title">Codici di recupero</h1>
    <p class="sg-auth-subtitle">
        Salva questi codici in un luogo sicuro. Ciascuno può essere usato <strong>una sola volta</strong>
        per accedere se perdi il dispositivo. Non verranno mostrati di nuovo.
    </p>

    <div class="alert alert-warning sg-mb-2">
        <i class="fas fa-exclamation-triangle mr-1"></i>
        Conserva questi codici in modo sicuro (password manager, documento stampato).
        Non potrai visualizzarli nuovamente.
    </div>

    <div style="background:#f8f9fa; border:1px solid #dee2e6; border-radius:4px; padding:1rem; margin-bottom:1rem;">
        @foreach($codes as $code)
            <code style="display:block; font-size:1rem; letter-spacing:.05em; margin-bottom:.35rem;">{{ $code }}</code>
        @endforeach
    </div>

    <form method="POST" action="{{ route('2fa.codes.confirm') }}">
        @csrf
        <button type="submit" class="sg-btn sg-btn-primary sg-btn-block">
            <i class="fas fa-check mr-1"></i> Ho salvato i codici di recupero
        </button>
    </form>
</x-guest-layout>
