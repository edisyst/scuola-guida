@extends('layouts.admin')

@section('title', 'Modalità Studio')

@section('content_header')@endsection

@section('content')
<div class="sg-wrapper">

    <div class="sg-header">
        <p class="sg-header-subtitle">Allenati senza timer e senza punteggio</p>
        <h1 class="sg-header-title"><i class="fas fa-graduation-cap mr-2"></i> Modalità Studio</h1>
    </div>

    @if($hasSession)
        {{-- Su mobile alert + azioni vanno in colonna (flex-wrap) --}}
        <div class="alert alert-info d-flex flex-column flex-md-row justify-content-between align-items-md-center sg-gap-2 sg-mb-3">
            <div>
                <i class="fas fa-info-circle"></i>
                Hai una sessione di studio in corso.
            </div>
            <div class="d-flex flex-wrap sg-gap-2">
                <a href="{{ route('study.play') }}" class="sg-btn sg-btn-primary sg-btn-sm">
                    <i class="fas fa-play"></i> Riprendi
                </a>
                <form action="{{ route('study.destroy') }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button class="sg-btn sg-btn-outline sg-btn-sm"
                            onclick="return confirm('Vuoi davvero terminare la sessione in corso?');">
                        <i class="fas fa-times"></i> Termina
                    </button>
                </form>
            </div>
        </div>
    @endif

    <div class="sg-card" style="max-width: 800px; margin: 0 auto;">
        <div class="card-body p-4">
            <h5 class="mb-3">Scegli da dove studiare</h5>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('study.start') }}" method="POST">
                @csrf

                {{-- ── Quiz specifico ───────────────────────────────── --}}
                <div class="form-group">
                    <div class="custom-control custom-radio">
                        <input type="radio" id="source-quiz" name="source" value="quiz"
                               class="custom-control-input"
                               {{ old('source') === 'quiz' ? 'checked' : '' }}>
                        <label class="custom-control-label font-weight-bold" for="source-quiz">
                            Da un quiz specifico
                        </label>
                    </div>
                    <div class="mt-2 ml-4">
                        <select name="quiz_id" class="form-control"
                                onfocus="document.getElementById('source-quiz').checked = true;">
                            <option value="">— Seleziona un quiz —</option>
                            @foreach($quizzes as $quiz)
                                <option value="{{ $quiz->id }}"
                                    {{ (int) old('quiz_id') === $quiz->id ? 'selected' : '' }}>
                                    {{ $quiz->title }} ({{ $quiz->questions_count }} domande)
                                </option>
                            @endforeach
                        </select>
                        @if($quizzes->isEmpty())
                            <small class="text-muted">Nessun quiz pubblicato disponibile.</small>
                        @endif
                    </div>
                </div>

                <hr>

                {{-- ── Categoria ────────────────────────────────────── --}}
                <div class="form-group">
                    <div class="custom-control custom-radio">
                        <input type="radio" id="source-category" name="source" value="category"
                               class="custom-control-input"
                               {{ old('source') === 'category' ? 'checked' : '' }}>
                        <label class="custom-control-label font-weight-bold" for="source-category">
                            Da una categoria
                        </label>
                    </div>
                    <div class="mt-2 ml-4">
                        <select name="category_id" class="form-control"
                                onfocus="document.getElementById('source-category').checked = true;">
                            <option value="">— Seleziona una categoria —</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}"
                                    {{ (int) old('category_id') === $category->id ? 'selected' : '' }}>
                                    {{ $category->name }} ({{ $category->questions_count }} domande)
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <hr>

                {{-- ── Random ───────────────────────────────────────── --}}
                <div class="form-group">
                    <div class="custom-control custom-radio">
                        <input type="radio" id="source-random" name="source" value="random"
                               class="custom-control-input"
                               {{ old('source', 'random') === 'random' ? 'checked' : '' }}>
                        <label class="custom-control-label font-weight-bold" for="source-random">
                            Domande casuali
                        </label>
                    </div>
                    <small class="text-muted ml-4">
                        Verranno estratte fino a {{ \App\Services\StudyService::RANDOM_LIMIT }} domande casuali da tutto il database.
                    </small>
                </div>

                <div class="text-right mt-4">
                    <button type="submit" class="sg-btn sg-btn-primary">
                        <i class="fas fa-play"></i> Inizia a studiare
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
