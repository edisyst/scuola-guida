@extends('layouts.admin')

@section('header', 'Gestione Domande Quiz')

@section('content')

    <h4>{{ $quiz->title }}</h4>

    <div class="row">

        {{-- DOMANDE DISPONIBILI --}}
        <div class="col-md-6">
            <h5>Tutte le domande</h5>

            @foreach($questions as $q)
                <div class="border p-2 mb-2">

                    {{ Str::limit($q->question, 60) }}

                    @if(!$quiz->questions->contains($q->id))
                        <form method="POST"
                              action="{{ route('admin.quizzes.questions.add', $quiz) }}">
                            @csrf
                            <input type="hidden" name="question_id" value="{{ $q->id }}">
                            <button class="btn btn-sm btn-success">Aggiungi</button>
                        </form>
                    @endif

                </div>
            @endforeach
        </div>

        {{-- DOMANDE NEL QUIZ --}}
        <div class="col-md-6">
            <h5>Domande nel quiz</h5>

            @foreach($quiz->questions as $q)
                <div class="border p-2 mb-2">

                    {{ Str::limit($q->question, 60) }}

                    <form method="POST"
                          action="{{ route('admin.quizzes.questions.remove', $quiz) }}">
                        @csrf
                        <input type="hidden" name="question_id" value="{{ $q->id }}">
                        <button class="btn btn-sm btn-danger">Rimuovi</button>
                    </form>

                </div>
            @endforeach
        </div>

    </div>

@endsection
