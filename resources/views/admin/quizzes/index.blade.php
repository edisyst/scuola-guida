@extends('layouts.admin')

@section('title', 'Quiz')
@section('content_header')@endsection

@section('content')
<div class="sg-wrapper">

    <div class="sg-header sg-flex-between">
        <div>
            <p class="sg-header-subtitle">Catalogo</p>
            <h1 class="sg-header-title"><i class="fas fa-clipboard-check mr-2"></i> Quiz</h1>
        </div>
        @if(auth()->user()->canCreateQuiz())
            <div class="sg-header-actions">
                <a href="{{ route('admin.quizzes.create') }}" class="sg-btn sg-btn-light sg-btn-sm">
                    <i class="fas fa-plus"></i> Nuovo Quiz
                </a>
                <form method="POST" action="{{ route('admin.quizzes.random') }}" style="display:inline;">
                    @csrf
                    <button class="sg-btn sg-btn-success sg-btn-sm">
                        <i class="fas fa-random"></i> Quiz Random
                    </button>
                </form>
            </div>
        @endif
    </div>

    <div class="sg-card">
        <div class="table-responsive">
            <table class="sg-table" id="quiz-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Titolo</th>
                        <th>Stato</th>
                        <th>Domande</th>
                        <th style="width:280px;text-align:right;">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($quizzes as $quiz)
                        <tr>
                            <td class="sg-text-muted">{{ $quiz->id }}</td>
                            <td><strong>{{ $quiz->title }}</strong></td>
                            <td>
                                @if($quiz->is_active)
                                    <span class="sg-badge sg-badge-success">Attivo</span>
                                @else
                                    <span class="sg-badge">Disattivo</span>
                                @endif
                            </td>
                            <td>
                                <span class="sg-badge sg-badge-info">{{ $quiz->questions_count ?? 0 }}/{{ $quiz->max_questions ?? 0 }}</span>
                            </td>
                            <td class="sg-actions-cell">
                                <a href="{{ route('quiz.play', $quiz) }}" class="sg-btn-icon info" title="Play">
                                    <i class="fas fa-play"></i>
                                </a>
                                @if(auth()->user()->canEditQuiz())
                                    <a href="{{ route('admin.quizzes.questions', $quiz) }}" class="sg-btn-icon info" title="Gestisci domande">
                                        <i class="fas fa-list-check"></i>
                                    </a>
                                    <a href="{{ route('admin.quizzes.edit', $quiz) }}" class="sg-btn-icon edit" title="Modifica">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                @endif
                                @if(auth()->user()->canDeleteQuiz())
                                    <form method="POST" action="{{ route('admin.quizzes.destroy', $quiz) }}" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button class="sg-btn-icon delete" title="Elimina" onclick="return confirm('Sei sicuro?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('js')
    @parent
    <script>
        $('#quiz-table').DataTable({
            columnDefs: [{ orderable: false, targets: 4 }]
        });
    </script>
@stop
