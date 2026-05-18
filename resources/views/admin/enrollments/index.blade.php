@extends('layouts.admin')

@section('title', 'Iscrizioni quiz')
@section('content_header')@endsection

@section('content')
<div class="sg-wrapper">

    <div class="sg-header sg-flex-between">
        <div>
            <p class="sg-header-subtitle">Gestione</p>
            <h1 class="sg-header-title"><i class="fas fa-user-check mr-2"></i> Iscrizioni quiz</h1>
        </div>
        @if($pendingCount > 0)
            <span class="sg-badge sg-badge-warning">
                <i class="fas fa-clock"></i> {{ $pendingCount }} in attesa
            </span>
        @endif
    </div>

    <div class="sg-card sg-mb-3">
        <div class="sg-card-body sg-flex flex-wrap" style="gap:8px;">
            <a href="{{ route('admin.enrollments.index') }}"
               class="sg-btn sg-btn-sm {{ !$status ? 'sg-btn-primary' : 'sg-btn-light' }}">Tutte</a>
            @foreach(\App\Models\QuizEnrollment::STATUSES as $key => $label)
                <a href="{{ route('admin.enrollments.index', ['status' => $key]) }}"
                   class="sg-btn sg-btn-sm {{ $status === $key ? 'sg-btn-primary' : 'sg-btn-light' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>

    <div class="sg-card">
        @if($enrollments->isEmpty())
            <div class="sg-table-empty">Nessuna iscrizione trovata.</div>
        @else
            <div class="table-responsive">
                <table class="sg-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Quiz</th>
                            <th>Utente</th>
                            <th>Stato</th>
                            <th>Richiesta</th>
                            <th>Revisionata da</th>
                            <th class="text-right">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($enrollments as $enrollment)
                            <tr>
                                <td class="sg-text-muted">{{ $enrollment->id }}</td>
                                <td><strong>{{ $enrollment->quiz->title ?? '—' }}</strong></td>
                                <td>{{ $enrollment->user->name ?? '—' }}</td>
                                <td>
                                    @switch($enrollment->status)
                                        @case(\App\Models\QuizEnrollment::STATUS_PENDING)
                                            <span class="sg-badge sg-badge-warning">In attesa</span>
                                            @break
                                        @case(\App\Models\QuizEnrollment::STATUS_APPROVED)
                                            <span class="sg-badge sg-badge-success">Approvata</span>
                                            @break
                                        @case(\App\Models\QuizEnrollment::STATUS_REJECTED)
                                            <span class="sg-badge sg-badge-danger">Rifiutata</span>
                                            @break
                                        @case(\App\Models\QuizEnrollment::STATUS_COMPLETED)
                                            <span class="sg-badge sg-badge-info">Completata</span>
                                            @break
                                    @endswitch
                                </td>
                                <td class="sg-text-muted">{{ $enrollment->created_at->format('d/m/Y H:i') }}</td>
                                <td class="sg-text-muted">
                                    {{ $enrollment->reviewer->name ?? '—' }}
                                </td>
                                <td class="text-right">
                                    {{-- bottoni azione: gap-2 separa Approva e Rifiuta --}}
                                    <div class="d-inline-flex gap-2 align-items-center">
                                        @if($enrollment->isPending())
                                            <form method="POST" action="{{ route('admin.enrollments.approve', $enrollment) }}" class="d-inline">
                                                @csrf
                                                <button class="sg-btn sg-btn-success sg-btn-sm">
                                                    <i class="fas fa-check"></i> Approva
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.enrollments.reject', $enrollment) }}" class="d-inline">
                                                @csrf
                                                <button class="sg-btn sg-btn-outline sg-btn-sm">
                                                    <i class="fas fa-times"></i> Rifiuta
                                                </button>
                                            </form>
                                        @elseif($enrollment->isCompleted() || $enrollment->isRejected())
                                            <form method="POST"
                                                  action="{{ route('admin.enrollments.reopen', ['quiz' => $enrollment->quiz_id, 'user' => $enrollment->user_id]) }}"
                                                  class="d-inline"
                                                  onsubmit="return confirm('Riaprire una nuova iscrizione approvata per questo utente?');">
                                                @csrf
                                                <button class="sg-btn sg-btn-light sg-btn-sm">
                                                    <i class="fas fa-redo"></i> Riapri
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="sg-card-section">
                {{ $enrollments->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
