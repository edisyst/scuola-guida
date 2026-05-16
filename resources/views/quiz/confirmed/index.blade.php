@extends('layouts.admin')

@section('title', 'Quiz disponibili')
@section('content_header')@endsection

@section('content')
<div class="sg-wrapper">

    <div class="sg-header">
        <p class="sg-header-subtitle">Quiz ufficiali</p>
        <h1 class="sg-header-title"><i class="fas fa-clipboard-check mr-2"></i> Quiz disponibili</h1>
    </div>

    @if(!$canEnroll)
        <div class="alert alert-warning sg-mb-3">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>Iscrizione anagrafica necessaria.</strong>
            Per iscriverti agli esami ufficiali devi prima inviare i tuoi dati anagrafici dal
            <a href="{{ route('profile.edit') }}">tuo profilo</a> ed essere approvato dall'amministratore.
            @if($user->isRegistrationPending())
                La tua richiesta è in attesa di revisione.
            @elseif($user->isRegistrationRejected())
                La tua precedente richiesta è stata rifiutata: correggi i dati e reinviala.
            @endif
            Nel frattempo puoi sempre <strong>esercitarti con i quiz casuali</strong>.
        </div>
    @endif

    <div class="sg-card">
        @if($quizzes->isEmpty())
            <div class="sg-table-empty">Nessun quiz confermato disponibile al momento.</div>
        @else
            <div class="table-responsive">
                <table class="sg-table">
                    <thead>
                        <tr>
                            <th>Titolo</th>
                            <th>Domande</th>
                            <th>Tempo</th>
                            <th>Stato iscrizione</th>
                            <th style="text-align:right;">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($quizzes as $quiz)
                            @php
                                $userEnrollments = $enrollments->get($quiz->id) ?? collect();
                                $latest          = $userEnrollments->first();
                                $active          = $userEnrollments->firstWhere(fn ($e) => in_array($e->status, [
                                    \App\Models\QuizEnrollment::STATUS_PENDING,
                                    \App\Models\QuizEnrollment::STATUS_APPROVED,
                                ]));
                            @endphp
                            <tr>
                                <td><strong>{{ $quiz->title }}</strong></td>
                                <td>{{ $quiz->questions_count }}</td>
                                <td class="sg-text-muted">
                                    {{ $quiz->time_limit ? gmdate('i\'', $quiz->time_limit) : '—' }}
                                </td>
                                <td>
                                    @if($active && $active->isPending())
                                        <span class="sg-badge sg-badge-warning">In attesa</span>
                                    @elseif($active && $active->isApproved())
                                        <span class="sg-badge sg-badge-success">Approvata</span>
                                    @elseif($latest && $latest->isCompleted())
                                        <span class="sg-badge sg-badge-info">Già svolto</span>
                                    @elseif($latest && $latest->isRejected())
                                        <span class="sg-badge sg-badge-danger">Rifiutata</span>
                                    @else
                                        <span class="sg-text-muted">—</span>
                                    @endif
                                </td>
                                <td style="text-align:right;">
                                    @if($active && $active->isApproved())
                                        <a href="{{ route('quiz.play', $quiz) }}"
                                           class="sg-btn sg-btn-primary sg-btn-sm"
                                           onclick="return confirm('Puoi svolgere questo quiz una sola volta. Procedere?');">
                                            <i class="fas fa-play"></i> Svolgi
                                        </a>
                                    @elseif($active && $active->isPending())
                                        <span class="sg-text-muted"><i class="fas fa-hourglass-half"></i> Attendi approvazione</span>
                                    @elseif($latest && $latest->isCompleted())
                                        <span class="sg-text-muted">Tentativo già usato</span>
                                    @elseif(!$canEnroll)
                                        <a href="{{ route('profile.edit') }}" class="sg-btn sg-btn-light sg-btn-sm">
                                            <i class="fas fa-id-card"></i> Completa profilo
                                        </a>
                                    @else
                                        <form method="POST" action="{{ route('quiz.enrollments.store', $quiz) }}" style="display:inline;">
                                            @csrf
                                            <button class="sg-btn sg-btn-outline sg-btn-sm">
                                                <i class="fas fa-paper-plane"></i> Richiedi iscrizione
                                            </button>
                                        </form>
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
