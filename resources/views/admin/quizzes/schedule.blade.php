@extends('layouts.admin')

@section('title', 'Schedulazione iscrizioni')
@section('content_header')@endsection

@section('content')
<div class="sg-wrapper-sm">

    <div class="sg-header sg-flex-between">
        <div>
            <p class="sg-header-subtitle">Quiz confermato</p>
            <h1 class="sg-header-title">
                <i class="fas fa-calendar-alt mr-2"></i> Schedulazione iscrizioni
            </h1>
        </div>
        <a href="{{ route('admin.quizzes.index') }}" class="sg-btn sg-btn-light sg-btn-sm">
            <i class="fas fa-arrow-left"></i> Indietro
        </a>
    </div>

    <form method="POST" action="{{ route('admin.quizzes.schedule.update', $quiz) }}">
        @csrf
        @method('PUT')

        <div class="sg-card">
            <div class="sg-card-body">
                <p class="sg-text-muted">
                    Quiz: <strong>{{ $quiz->title }}</strong>
                </p>

                <p class="sg-text-muted small">
                    Imposta la finestra di iscrizione per i viewer.
                    Entrambi i campi sono facoltativi: se lasciati vuoti, le iscrizioni seguono le regole standard.
                </p>

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <h3 class="sg-form-section-title mt-3">Schedulazione iscrizioni</h3>

                <div class="sg-form-group">
                    <label for="enrollments_open_at" class="sg-form-label">Apertura iscrizioni</label>
                    <input type="datetime-local"
                           id="enrollments_open_at"
                           name="enrollments_open_at"
                           class="sg-form-control @error('enrollments_open_at') is-invalid @enderror"
                           value="{{ old('enrollments_open_at', optional($quiz->enrollments_open_at)->format('Y-m-d\TH:i')) }}">
                    @error('enrollments_open_at')
                        <div class="sg-form-error">{{ $message }}</div>
                    @enderror
                    <small class="sg-form-hint">Prima di questa data il pulsante "Richiedi iscrizione" sarà nascosto.</small>
                </div>

                <div class="sg-form-group">
                    <label for="enrollments_close_at" class="sg-form-label">Chiusura iscrizioni</label>
                    <input type="datetime-local"
                           id="enrollments_close_at"
                           name="enrollments_close_at"
                           class="sg-form-control @error('enrollments_close_at') is-invalid @enderror"
                           value="{{ old('enrollments_close_at', optional($quiz->enrollments_close_at)->format('Y-m-d\TH:i')) }}">
                    @error('enrollments_close_at')
                        <div class="sg-form-error">{{ $message }}</div>
                    @enderror
                    <small class="sg-form-hint">Dopo questa data le iscrizioni pending verranno chiuse automaticamente.</small>
                </div>
            </div>
        </div>

        <div class="sg-mt-3 sg-text-center">
            <button class="sg-btn sg-btn-primary">
                <i class="fas fa-save"></i> Salva schedulazione
            </button>
        </div>
    </form>
</div>
@endsection
