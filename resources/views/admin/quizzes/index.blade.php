@extends('layouts.admin')

@section('header', 'Quiz')

@section('content')

<a href="{{ route('admin.quizzes.create') }}" class="btn btn-primary mb-3">
    Nuovo Quiz
</a>

<table class="table table-bordered" id="quiz-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Titolo</th>
            <th>Attivo</th>
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

                <td>
                    <a href="{{ route('quiz.play', $quiz) }}" class="btn btn-sm btn-info">Play</a>

                    <a href="{{ route('admin.quizzes.edit', $quiz) }}" class="btn btn-sm btn-warning">Modifica</a>

                    <form method="POST" action="{{ route('admin.quizzes.destroy', $quiz) }}" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-danger">Elimina</button>
                    </form>
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
