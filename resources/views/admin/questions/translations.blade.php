@extends('layouts.admin')

@section('title', 'Traduzioni domanda')
@section('content_header')@endsection

@section('content')
<div class="sg-wrapper-sm">

    <div class="sg-header sg-flex-between">
        <div>
            <p class="sg-header-subtitle">Catalogo · Accessibilità</p>
            <h1 class="sg-header-title"><i class="fas fa-language mr-2"></i> Traduzioni domanda</h1>
        </div>
        <a href="{{ route('admin.questions.edit', $question) }}" class="sg-btn sg-btn-light sg-btn-sm">
            <i class="fas fa-arrow-left"></i> Torna alla domanda
        </a>
    </div>

    {{-- Testo originale (italiano) — fonte di verità, non traducibile --}}
    <div class="sg-card sg-mb-3">
        <div class="sg-card-header">
            <h2 class="sg-card-header-title">
                <i class="fas fa-flag mr-2"></i> Testo originale (Italiano)
            </h2>
        </div>
        <div class="sg-card-body">
            <p class="mb-0">{{ $question->question }}</p>
        </div>
    </div>

    {{-- Traduzioni esistenti --}}
    <div class="sg-card sg-mb-3">
        <div class="sg-card-header">
            <h2 class="sg-card-header-title">Traduzioni esistenti</h2>
        </div>
        <div class="sg-card-body">
            @forelse($translations as $translation)
                <div class="border rounded p-3 mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge badge-info text-uppercase">
                            {{ config('locales.exam.' . $translation->locale, $translation->locale) }}
                            <small>({{ $translation->locale }})</small>
                        </span>
                        <form action="{{ route('admin.questions.translations.destroy', [$question, $translation->locale]) }}"
                              method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="sg-btn sg-btn-danger sg-btn-sm"
                                    onclick="return confirm('Eliminare questa traduzione?')">
                                <i class="fas fa-trash"></i> Elimina
                            </button>
                        </form>
                    </div>
                    <form action="{{ route('admin.questions.translations.update', [$question, $translation->locale]) }}"
                          method="POST">
                        @csrf
                        @method('PUT')
                        <div class="form-group mb-2">
                            <textarea name="text" rows="3" maxlength="1000"
                                      class="form-control @error('text') is-invalid @enderror"
                                      required>{{ old('text', $translation->text) }}</textarea>
                            @error('text')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                        <button type="submit" class="sg-btn sg-btn-primary sg-btn-sm">
                            <i class="fas fa-save"></i> Salva
                        </button>
                    </form>
                </div>
            @empty
                <div class="text-center text-muted py-4">
                    <i class="fas fa-language fa-3x text-muted mb-3"></i>
                    <p class="mb-0">Nessuna traduzione presente per questa domanda.</p>
                    <p class="text-muted">Aggiungine una usando il modulo qui sotto.</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Aggiungi nuova traduzione --}}
    @if($available->isNotEmpty())
        <div class="sg-card">
            <div class="sg-card-header">
                <h2 class="sg-card-header-title">
                    <i class="fas fa-plus mr-2"></i> Aggiungi traduzione
                </h2>
            </div>
            <div class="sg-card-body">
                <form action="{{ route('admin.questions.translations.store', $question) }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label for="locale">Lingua</label>
                        <select name="locale" id="locale"
                                class="form-control @error('locale') is-invalid @enderror" required>
                            @foreach($available as $code => $label)
                                <option value="{{ $code }}" @selected(old('locale') === $code)>
                                    {{ $label }} ({{ $code }})
                                </option>
                            @endforeach
                        </select>
                        @error('locale')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="text">Testo tradotto</label>
                        <textarea name="text" id="text" rows="3" maxlength="1000"
                                  class="form-control @error('text') is-invalid @enderror"
                                  required>{{ old('text') }}</textarea>
                        @error('text')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                    <button type="submit" class="sg-btn sg-btn-primary">
                        <i class="fas fa-save"></i> Aggiungi traduzione
                    </button>
                </form>
            </div>
        </div>
    @else
        <div class="alert alert-success">
            <i class="fas fa-check-circle mr-1"></i>
            Tutte le lingue d'esame disponibili sono già state tradotte.
        </div>
    @endif

</div>
@endsection
