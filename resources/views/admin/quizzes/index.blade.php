@extends('layouts.admin')

@section('header', 'Quiz')

@section('content')

    <div class="mb-3 d-flex">

        <a href="{{ route('admin.quizzes.create') }}" class="btn btn-primary mr-2">
            Nuovo Quiz
        </a>

        <form method="POST" action="{{ route('admin.quizzes.random') }}">
            @csrf
            <button class="btn btn-success">
                Crea Quiz Random
            </button>
        </form>

    </div>

    <table class="table table-bordered" id="quiz-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Titolo</th>
                <th>Attivo</th>
                <th>Domande</th>
                <th>Azioni</th>
            </tr>
        </thead>

        <tbody>
            @foreach($quizzes as $quiz)
                <tr>
                    <td>{{ $quiz->id }}</td>
                    <td>{{ $quiz->title }}</td>

                    <td>
                        @if($quiz->is_active)
                            <span class="badge badge-success">Attivo</span>
                        @else
                            <span class="badge badge-secondary">Disattivo</span>
                        @endif
                    </td>
                    <td><span class="badge badge-info">{{ $quiz->questions_count  ?? 0}}</span></td>

                    <td>

                        <div class="d-flex flex-wrap gap-1">

                            {{-- PLAY --}}
                            <a href="{{ route('quiz.play', $quiz) }}"
                               class="btn btn-sm btn-info mb-1">
                                Play
                            </a>

                            {{-- GESTIONE DOMANDE --}}
                            <a href="{{ route('admin.quizzes.questions', $quiz) }}"
                               class="btn btn-sm btn-secondary mb-1">
                                Domande
                            </a>

                            {{-- EDIT --}}
                            <a href="{{ route('admin.quizzes.edit', $quiz) }}"
                               class="btn btn-sm btn-warning mb-1">
                                Modifica
                            </a>

                            {{-- DELETE --}}
                            <form method="POST"
                                  action="{{ route('admin.quizzes.destroy', $quiz) }}"
                                  style="display:inline;">
                                @csrf
                                @method('DELETE')

                                <button class="btn btn-sm btn-danger mb-1"
                                        onclick="return confirm('Sei sicuro?')">
                                    Elimina
                                </button>
                            </form>

                        </div>

                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

@endsection

@section('js')
    @parent
    <script>
        $('#quiz-table').DataTable();
    </script>
@stop
