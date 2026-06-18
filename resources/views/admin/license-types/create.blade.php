@extends('layouts.admin')

@section('page-title', 'Nuovo tipo di patente')

@section('content_header')@endsection

@section('content')
<div class="sg-wrapper-fluid">

    <div class="sg-header sg-flex-between">
        <h1 class="sg-header-title">Nuovo tipo di patente</h1>
        <div class="sg-header-actions">
            <a href="{{ route('admin.license-types.index') }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left mr-1"></i> Torna all'elenco
            </a>
        </div>
    </div>

    <div class="sg-card">
        <div class="sg-card-body">
            <form method="POST" action="{{ route('admin.license-types.store') }}">
                @csrf

                <div class="form-group">
                    <label for="code">Codice <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code') }}" required maxlength="10">
                    @error('code')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="name">Nome <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required maxlength="100">
                    @error('name')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="description">Descrizione</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3" maxlength="500">{{ old('description') }}</textarea>
                    @error('description')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <hr>

                <h5>Formato esame (opzionale)</h5>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="exam_questions">N. domande</label>
                            <input type="number" class="form-control @error('exam_questions') is-invalid @enderror" id="exam_questions" name="exam_questions" value="{{ old('exam_questions') }}" min="1">
                            @error('exam_questions')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="exam_minutes">Minuti</label>
                            <input type="number" class="form-control @error('exam_minutes') is-invalid @enderror" id="exam_minutes" name="exam_minutes" value="{{ old('exam_minutes') }}" min="1">
                            @error('exam_minutes')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="exam_max_errors">Max errori</label>
                            <input type="number" class="form-control @error('exam_max_errors') is-invalid @enderror" id="exam_max_errors" name="exam_max_errors" value="{{ old('exam_max_errors') }}" min="1">
                            @error('exam_max_errors')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="sort_order">Ordinamento</label>
                    <input type="number" class="form-control @error('sort_order') is-invalid @enderror" id="sort_order" name="sort_order" value="{{ old('sort_order', 0) }}" min="0">
                    @error('sort_order')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                        Attivo
                    </label>
                </div>

                <hr>

                <h5>Categorie associate</h5>

                @if ($categories->isEmpty())
                    <p class="text-muted">Nessuna categoria disponibile.</p>
                @else
                    <div class="form-group">
                        @foreach ($categories as $category)
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="category_{{ $category->id }}" name="category_ids[]" value="{{ $category->id }}">
                                <label class="form-check-label" for="category_{{ $category->id }}">
                                    {{ $category->name }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="form-group mt-3">
                    <button type="submit" class="btn btn-primary">Crea</button>
                    <a href="{{ route('admin.license-types.index') }}" class="btn btn-secondary">Annulla</a>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
