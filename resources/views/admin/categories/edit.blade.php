@extends('layouts.admin')

@section('title', 'Modifica categoria')
@section('content_header')@endsection

@section('content')
<div class="sg-wrapper-sm">

    <div class="sg-header sg-flex-between">
        <div>
            <p class="sg-header-subtitle">Catalogo</p>
            <h1 class="sg-header-title"><i class="fas fa-edit mr-2"></i> Modifica categoria</h1>
        </div>
        <a href="{{ route('admin.categories.index') }}" class="sg-btn sg-btn-light sg-btn-sm">
            <i class="fas fa-arrow-left"></i> Indietro
        </a>
    </div>

    <form action="{{ route('admin.categories.update', $category) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="sg-card">
            <div class="sg-card-body">
                @include('admin.categories.partials.form')
            </div>
        </div>

        <div class="sg-mt-3 sg-text-center">
            <button class="sg-btn sg-btn-primary">
                <i class="fas fa-save"></i> Aggiorna
            </button>
        </div>
    </form>

    {{-- ------------------------------------------------------------------ --}}
    {{-- SEZIONE TRADUZIONI                                                   --}}
    {{-- ------------------------------------------------------------------ --}}

    {{-- Traduzioni esistenti --}}
    <div class="sg-card sg-mt-4 sg-mb-3">
        <div class="sg-card-header">
            <h2 class="sg-card-header-title">
                <i class="fas fa-language mr-2"></i> Traduzioni
            </h2>
        </div>
        <div class="sg-card-body">
            @forelse($translations as $translation)
                <div class="border rounded p-3 mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge badge-info text-uppercase">
                            {{ config('locales.exam.' . $translation->locale, $translation->locale) }}
                            <small>({{ $translation->locale }})</small>
                        </span>
                        <form action="{{ route('admin.categories.translations.destroy', [$category, $translation->locale]) }}"
                              method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="sg-btn sg-btn-danger sg-btn-sm"
                                    onclick="return confirm('Eliminare questa traduzione?')">
                                <i class="fas fa-trash"></i> Elimina
                            </button>
                        </form>
                    </div>
                    <form action="{{ route('admin.categories.translations.update', [$category, $translation->locale]) }}"
                          method="POST">
                        @csrf
                        @method('PUT')
                        <div class="form-group mb-2">
                            <input type="text" name="name" maxlength="255"
                                   class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $translation->name) }}" required>
                            @error('name')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                        <button type="submit" class="sg-btn sg-btn-primary sg-btn-sm">
                            <i class="fas fa-save"></i> Salva
                        </button>
                    </form>
                </div>
            @empty
                <div class="text-center text-muted py-3">
                    <i class="fas fa-language fa-3x text-muted mb-3 d-block"></i>
                    <p class="mb-0">Nessuna traduzione presente.</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Aggiungi nuova traduzione --}}
    @if($available->isNotEmpty())
        <div class="sg-card sg-mb-3">
            <div class="sg-card-header">
                <h2 class="sg-card-header-title">
                    <i class="fas fa-plus mr-2"></i> Aggiungi traduzione
                </h2>
            </div>
            <div class="sg-card-body">
                <form action="{{ route('admin.categories.translations.store', $category) }}" method="POST">
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
                        <label for="name">Nome tradotto</label>
                        <input type="text" name="name" id="name" maxlength="255"
                               class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name') }}" required>
                        @error('name')
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
        <div class="alert alert-success sg-mb-3">
            <i class="fas fa-check-circle mr-1"></i>
            Tutte le lingue d'esame disponibili sono già state tradotte.
        </div>
    @endif

</div>
@endsection
