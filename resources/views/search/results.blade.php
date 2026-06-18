@extends('layouts.admin')

@section('title', 'Risultati ricerca')
@section('content_header')@endsection

@section('css')
@parent
@stop

@section('content')
<div class="sg-wrapper">

    <div class="sg-header">
        <p class="sg-header-subtitle">Ricerca</p>
        <h1 class="sg-header-title">
            <i class="fas fa-magnifying-glass mr-2"></i> Risultati per <em>"{{ $q }}"</em>
        </h1>
    </div>

    @if($q === '')
        <div class="sg-card">
            <div class="sg-card-body sg-text-muted sg-text-center">
                <i class="fas fa-info-circle"></i> Inserisci una parola chiave nella barra di ricerca in alto.
            </div>
        </div>
    @else

        <p class="sg-text-muted sg-mb-3">
            {{ $questions->count() + $categories->count() }} risultati trovati
            ({{ $questions->count() }} {{ Str::plural('domanda', $questions->count()) }},
            {{ $categories->count() }} {{ Str::plural('categoria', $categories->count()) }})
        </p>

        {{-- CATEGORIE --}}
        @if($categories->isNotEmpty())
            <div class="sg-card sg-mb-3">
                <div class="sg-card-header">
                    <h2 class="sg-card-header-title">
                        <i class="fas fa-folder"></i> Categorie ({{ $categories->count() }})
                    </h2>
                </div>
                <div>
                    @foreach($categories as $category)
                        <div class="sg-card-section sg-flex-between">
                            <span>
                                <i class="fas fa-folder-open sg-text-primary mr-2"></i>
                                <strong>{{ $category->name }}</strong>
                            </span>
                            @if(!empty($category->questions_count))
                                <span class="sg-badge">{{ $category->questions_count }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- DOMANDE --}}
        @if($questions->isNotEmpty())
            <div class="sg-card sg-mb-3">
                <div class="sg-card-header">
                    <h2 class="sg-card-header-title">
                        <i class="fas fa-question-circle"></i> Domande ({{ $questions->count() }})
                    </h2>
                </div>
                <div>
                    @foreach($questions as $question)
                        <div class="sg-card-section sg-flex-between align-items-start">
                            <span>
                                <i class="fas fa-circle {{ $question->is_true ? 'sg-text-success' : 'sg-text-danger' }} sg-status-dot-xs"></i>
                                {!! preg_replace('/(' . preg_quote($q, '/') . ')/iu', '<mark>$1</mark>', e($question->question)) !!}
                            </span>
                            <span class="sg-badge text-nowrap">
                                {{ $question->category->name ?? '—' }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if($questions->isEmpty() && $categories->isEmpty())
            <div class="sg-card">
                <div class="sg-card-body sg-text-muted sg-text-center">
                    <i class="fas fa-info-circle"></i>
                    Nessun risultato per <strong>"{{ $q }}"</strong>.
                </div>
            </div>
        @endif

    @endif
</div>
@endsection
