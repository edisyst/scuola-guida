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
                        <th style="width:360px;text-align:right;">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($quizzes as $quiz)
                        <tr>
                            <td class="sg-text-muted">{{ $quiz->id }}</td>
                            <td><strong>{{ $quiz->title }}</strong></td>
                            <td>
                                @if($quiz->isConfirmed())
                                    <span class="sg-badge sg-badge-info"><i class="fas fa-lock"></i> Confermato</span>
                                @elseif($quiz->isPublished())
                                    <span class="sg-badge sg-badge-success">Pubblicato</span>
                                @else
                                    <span class="sg-badge">Bozza</span>
                                @endif
                            </td>
                            <td>
                                <span class="sg-badge sg-badge-info">{{ $quiz->questions_count ?? 0 }}/{{ $quiz->max_questions ?? 0 }}</span>
                            </td>
                            <td class="sg-actions-cell">
                                @if(($quiz->questions_count ?? 0) > 0)
                                    <a href="{{ route('quiz.play', $quiz) }}" class="sg-btn-icon info" title="Play">
                                        <i class="fas fa-play"></i>
                                    </a>
                                @else
                                    <span class="sg-btn-icon info" title="Nessuna domanda nel quiz" style="opacity:.4;cursor:not-allowed;">
                                        <i class="fas fa-play"></i>
                                    </span>
                                @endif

                                @if(auth()->user()->canEditQuiz() && !$quiz->isLocked())
                                    <a href="{{ route('admin.quizzes.questions', $quiz) }}" class="sg-btn-icon info" title="Gestisci domande">
                                        <i class="fas fa-tasks"></i>
                                    </a>
                                @else
                                    <span class="sg-btn-icon info" title="Quiz confermato: domande bloccate" style="opacity:.4;cursor:not-allowed;">
                                        <i class="fas fa-tasks"></i>
                                    </span>
                                @endif

                                @if(auth()->user()->canBulkQuiz() && !$quiz->isLocked())
                                    <form method="POST" action="{{ route('admin.quizzes.fillRandom', $quiz) }}" style="display:inline;">
                                        @csrf
                                        @if(($quiz->questions_count ?? 0) === 0)
                                            <button class="sg-btn-icon success" title="Aggiungi domande random">
                                                <i class="fas fa-random"></i>
                                            </button>
                                        @else
                                            <span class="sg-btn-icon success" title="Il quiz ha già domande" style="opacity:.4;cursor:not-allowed;">
                                                <i class="fas fa-random"></i>
                                            </span>
                                        @endif
                                    </form>
                                @endif

                                @if(auth()->user()->isAdmin() && !$quiz->isConfirmed())
                                    @if($quiz->isPublished())
                                        <form method="POST" action="{{ route('admin.quizzes.unpublish', $quiz) }}" style="display:inline;">
                                            @csrf
                                            <button class="sg-btn-icon" title="Riporta in bozza">
                                                <i class="fas fa-eye-slash"></i>
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('admin.quizzes.publish', $quiz) }}" style="display:inline;">
                                            @csrf
                                            <button class="sg-btn-icon success" title="Pubblica">
                                                <i class="fas fa-globe"></i>
                                            </button>
                                        </form>
                                    @endif

                                    @if(($quiz->questions_count ?? 0) > 0)
                                        <form method="POST"
                                              action="{{ route('admin.quizzes.confirm', $quiz) }}"
                                              style="display:inline;"
                                              onsubmit="return confirm('Una volta confermato il quiz non potrà più essere modificato. Continuare?');">
                                            @csrf
                                            <button class="sg-btn-icon info" title="Conferma (lock)">
                                                <i class="fas fa-lock"></i>
                                            </button>
                                        </form>
                                    @endif
                                @endif

                                @if(auth()->user()->canDeleteQuiz() && !$quiz->isLocked())
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
            pageLength: 25,
            columnDefs: [{ orderable: false, targets: 4 }]
        });
    </script>
@stop
