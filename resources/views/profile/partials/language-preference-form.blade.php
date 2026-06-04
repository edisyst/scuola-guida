<p class="text-muted mb-3">
    Scegli la lingua in cui visualizzare il <strong>testo delle domande</strong> nella modalità studio,
    nel simulatore e nel test diagnostico. Se una traduzione non è disponibile, viene mostrato
    automaticamente il testo originale in italiano.
</p>

<form action="{{ route('profile.locale.update') }}" method="POST">
    @csrf

    <div class="form-group">
        <label for="locale">Lingua preferita</label>
        <select name="locale" id="locale"
                class="form-control @error('locale') is-invalid @enderror">
            <option value="">{{ config('locales.exam.' . config('locales.default', 'it'), 'Italiano') }} (predefinita)</option>
            @foreach(config('locales.exam', []) as $code => $label)
                @continue($code === config('locales.default', 'it'))
                <option value="{{ $code }}" @selected($user->locale === $code)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        @error('locale')
            <span class="invalid-feedback">{{ $message }}</span>
        @enderror
    </div>

    <button type="submit" class="btn btn-primary">
        <i class="fas fa-save mr-1"></i> Salva lingua
    </button>
</form>
