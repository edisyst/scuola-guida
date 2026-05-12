@extends('adminlte::page')

@section('title', 'Risultati ricerca')

@section('content_header')
    <h1>Risultati per <em>"{{ $q }}"</em></h1>
@stop

@section('content')

@if($q === '')
    <div class="callout callout-info">
        <p>Inserisci una parola chiave nella barra di ricerca in alto.</p>
    </div>
@else

    {{-- Totale --}}
    <p class="text-muted mb-3">
        {{ $questions->count() + $categories->count() }} risultati trovati
        ({{ $questions->count() }} {{ Str::plural('domanda', $questions->count()) }},
        {{ $categories->count() }} {{ Str::plural('categoria', $categories->count()) }})
    </p>

    {{-- CATEGORIE --}}
    @if($categories->isNotEmpty())
        <div class="card card-primary card-outline mb-3">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-folder mr-1"></i>
                    Categorie ({{ $categories->count() }})
                </h3>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @foreach($categories as $category)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>
                                <i class="fas fa-folder-open text-primary mr-2"></i>
                                {{ $category->name }}
                            </span>
                            <span class="badge badge-secondary">
                                {{ $category->questions_count ?? '' }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- DOMANDE --}}
    @if($questions->isNotEmpty())
        <div class="card card-success card-outline mb-3">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-question-circle mr-1"></i>
                    Domande ({{ $questions->count() }})
                </h3>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @foreach($questions as $question)
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <span>
                                    <i class="fas fa-circle mr-2 {{ $question->is_true ? 'text-success' : 'text-danger' }}" style="font-size:.55rem; vertical-align:middle"></i>
                                    {!! preg_replace('/(' . preg_quote($q, '/') . ')/iu', '<mark>$1</mark>', e($question->question)) !!}
                                </span>
                                <span class="badge badge-light text-muted ml-3 text-nowrap">
                                    {{ $question->category->name ?? '—' }}
                                </span>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- Nessun risultato --}}
    @if($questions->isEmpty() && $categories->isEmpty())
        <div class="callout callout-warning">
            <p>Nessun risultato per <strong>"{{ $q }}"</strong>.</p>
        </div>
    @endif

@endif

@stop
