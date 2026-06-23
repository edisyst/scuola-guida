@extends('layouts.admin')

@section('title', __('menu.prova_quiz'))
@section('content_header')@endsection

@section('content')
<div class="sg-wrapper">

    <div class="sg-header">
        <p class="sg-header-subtitle">{{ __('menu.quiz_test') }}</p>
        <h1 class="sg-header-title"><i class="fas fa-flask mr-2"></i> {{ __('menu.prova_quiz') }}</h1>
    </div>

    <div class="alert alert-info sg-mb-3">
        <i class="fas fa-info-circle"></i>
        I quiz in questa area sono per <strong>testare l'interfaccia</strong>. Le sessioni non vengono salvate e non influenzano statistiche o valutazioni.
    </div>

    {{-- Filtro tipo patente --}}
    @if($licenseTypes->isNotEmpty())
    <form method="GET" action="{{ route('quiz-test.index') }}" class="sg-mb-3 d-flex align-items-center" style="gap:.5rem">
        <select name="license_type_id" class="form-control" style="max-width:220px" onchange="this.form.submit()">
            <option value="">— Tutti i tipi patente —</option>
            @foreach($licenseTypes as $id => $label)
                <option value="{{ $id }}" {{ $licenseTypeId == $id ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        @if($licenseTypeId)
            <a href="{{ route('quiz-test.index') }}" class="sg-btn sg-btn-light sg-btn-sm">
                <i class="fas fa-times"></i> Rimuovi filtro
            </a>
        @endif
    </form>
    @endif

    <div class="sg-card">
        @if($quizzes->isEmpty())
            <div class="sg-table-empty">
                <i class="fas fa-clipboard fa-3x text-muted mb-3 d-block"></i>
                Nessun quiz disponibile al momento.
            </div>
        @else
            <div class="table-responsive">
                <table class="sg-table">
                    <thead>
                        <tr>
                            <th>Titolo</th>
                            <th>Domande</th>
                            <th>Tempo</th>
                            <th>Errori max</th>
                            <th class="text-right">Azione</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($quizzes as $quiz)
                        <tr>
                            <td><strong>{{ $quiz->title }}</strong>
                                @if($quiz->licenseType)
                                    <br><small class="sg-text-muted">{{ $quiz->licenseType->name }}</small>
                                @endif
                            </td>
                            <td>{{ $quiz->questions_count }}</td>
                            <td class="sg-text-muted">
                                {{ $quiz->time_limit ? gmdate('i\'', $quiz->time_limit) : '—' }}
                            </td>
                            <td class="sg-text-muted">{{ $quiz->max_errors ?? '—' }}</td>
                            <td class="text-right">
                                @if($quiz->questions_count > 0)
                                    <a href="{{ route('quiz-test.play', $quiz) }}"
                                       class="sg-btn sg-btn-primary sg-btn-sm">
                                        <i class="fas fa-play"></i> Prova
                                    </a>
                                @else
                                    <span class="sg-text-muted"><i class="fas fa-ban"></i> Nessuna domanda</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
