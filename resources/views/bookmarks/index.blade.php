@extends('layouts.admin')

@section('title', 'Domande salvate')

@section('content_header')@endsection

@section('content')
<div class="sg-wrapper">

    <div class="sg-header sg-flex-between">
        <div>
            <p class="sg-header-subtitle">Il tuo studio</p>
            <h1 class="sg-header-title">Domande salvate</h1>
        </div>
        <div class="sg-header-actions">
            @if($bookmarks->total() > 0)
                <form action="{{ route('study.start') }}" method="POST">
                    @csrf
                    <input type="hidden" name="source" value="bookmarks">
                    <button type="submit" class="sg-btn sg-btn-primary">
                        <i class="fas fa-play"></i> Studia le domande salvate
                    </button>
                </form>
            @endif
        </div>
    </div>

    {{-- Filtri --}}
    <div class="card mb-3">
        <div class="card-body py-2">
            <form action="{{ route('bookmarks.index') }}" method="GET" class="form-inline">
                <select name="category_id" class="form-control form-control-sm mr-2 mb-1">
                    <option value="">Tutte le categorie</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}"
                            {{ request('category_id') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="Cerca nel testo..."
                       class="form-control form-control-sm mr-2 mb-1">
                <button type="submit" class="btn btn-sm btn-primary mr-1 mb-1">
                    <i class="fas fa-search"></i> Filtra
                </button>
                <a href="{{ route('bookmarks.index') }}" class="btn btn-sm btn-outline-secondary mb-1">
                    <i class="fas fa-times"></i> Reset
                </a>
            </form>
        </div>
    </div>

    {{-- Empty state --}}
    @if($bookmarks->isEmpty())
        <div class="text-center py-5 text-muted">
            <i class="far fa-bookmark fa-3x mb-3"></i>
            <p class="mb-1">Non hai ancora salvato nessuna domanda.</p>
            <a href="{{ route('study.index') }}" class="btn btn-sm btn-outline-primary mt-2">
                <i class="fas fa-graduation-cap"></i> Vai alla modalità studio
            </a>
        </div>
    @else

        {{-- Cards domande --}}
        @foreach($bookmarks as $question)
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                @if($question->category)
                    <span class="badge badge-info">{{ $question->category->name }}</span>
                @else
                    <span></span>
                @endif
                <small class="text-muted">
                    Salvata il {{ $question->pivot->created_at->format('d/m/Y') }}
                </small>
            </div>
            <div class="card-body">
                <p class="font-weight-bold mb-3">{{ $question->question }}</p>

                @if($question->image)
                    <div class="mb-3">
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($question->image) }}"
                             alt="Immagine domanda"
                             class="img-fluid rounded"
                             style="max-height: 150px; object-fit: cover;">
                    </div>
                @endif

                <p class="mb-2">
                    Risposta corretta:
                    @if($question->is_true)
                        <strong class="text-success"><i class="fas fa-check"></i> Vero</strong>
                    @else
                        <strong class="text-danger"><i class="fas fa-times"></i> Falso</strong>
                    @endif
                </p>

                @if($question->pivot->note)
                    <div class="alert alert-light py-2 mb-0">
                        <i class="fas fa-sticky-note text-muted mr-1"></i>
                        {{ $question->pivot->note }}
                    </div>
                @endif
            </div>
            <div class="card-footer d-flex justify-content-between align-items-start">
                <livewire:bookmark-button :question-id="$question->id" :key="'bm-'.$question->id" />

                {{-- TODO: aggiungere link "Studia questa domanda" quando la modalità studio
                     supporterà il parametro ?question_id=X per pre-filtrare su una singola domanda --}}
            </div>
        </div>
        @endforeach

        {{-- Paginazione --}}
        <div class="d-flex justify-content-center">
            {{ $bookmarks->links() }}
        </div>

    @endif

</div>
@endsection
