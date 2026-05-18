@extends('layouts.admin')

@section('title', 'Riepilogo quiz')
@section('content_header')@endsection

@section('content')
<div class="sg-wrapper">

    <div class="sg-header sg-flex-between">
        <div>
            <p class="sg-header-subtitle">Quiz confermato</p>
            <h1 class="sg-header-title">
                <i class="fas fa-chart-bar mr-2"></i> {{ $quiz->title }}
            </h1>
        </div>
        <div class="sg-header-actions">
            <a href="{{ route('admin.quizzes.index') }}" class="sg-btn sg-btn-light sg-btn-sm">
                <i class="fas fa-arrow-left"></i> Indietro
            </a>
            <a href="{{ route('admin.quizzes.export-results', $quiz) }}"
               class="sg-btn sg-btn-success sg-btn-sm">
                <i class="fas fa-file-excel"></i> Esporta Excel
            </a>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $kpi['total'] }}</h3>
                    <p>Totale iscritti</p>
                </div>
                <div class="icon"><i class="fas fa-users"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $kpi['completed'] }}</h3>
                    <p>Hanno completato</p>
                </div>
                <div class="icon"><i class="fas fa-check-circle"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $kpi['pending'] }}</h3>
                    <p>Non ancora svolto</p>
                </div>
                <div class="icon"><i class="fas fa-hourglass-half"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ $kpi['average_score'] !== null ? number_format($kpi['average_score'], 1) . '%' : '—' }}</h3>
                    <p>Punteggio medio</p>
                </div>
                <div class="icon"><i class="fas fa-star"></i></div>
            </div>
        </div>
    </div>

    <div class="sg-card">
        @if($enrollments->isEmpty())
            <div class="sg-table-empty">Nessuna iscrizione registrata per questo quiz.</div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0 sg-table">
                    <thead>
                        <tr>
                            <th>Cognome</th>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Stato</th>
                            <th>Punteggio</th>
                            <th>Percentuale</th>
                            <th>Esito</th>
                            <th>Data tentativo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($enrollments as $enrollment)
                            @php
                                $user    = $enrollment->user;
                                $attempt = $enrollment->quizAttempt;
                                $total   = $attempt?->total_questions ?? 0;
                                $errors  = $total > 0 ? ($total - (int) $attempt->score) : null;
                                $passed  = $attempt && $total > 0 && $errors <= ($quiz->max_errors ?? 0);
                                $rowClass = !$attempt
                                    ? 'table-warning'
                                    : ($passed ? 'table-success' : 'table-danger');
                            @endphp
                            <tr class="{{ $rowClass }}">
                                <td><strong>{{ $user?->last_name ?: $user?->name ?: '—' }}</strong></td>
                                <td>{{ $user?->first_name ?: '—' }}</td>
                                <td class="sg-text-muted">{{ $user?->email ?: '—' }}</td>
                                <td>
                                    @switch($enrollment->status)
                                        @case(\App\Models\QuizEnrollment::STATUS_PENDING)
                                            <span class="sg-badge sg-badge-warning">In attesa</span>
                                            @break
                                        @case(\App\Models\QuizEnrollment::STATUS_APPROVED)
                                            <span class="sg-badge sg-badge-success">Approvata</span>
                                            @break
                                        @case(\App\Models\QuizEnrollment::STATUS_COMPLETED)
                                            <span class="sg-badge sg-badge-info">Completata</span>
                                            @break
                                        @case(\App\Models\QuizEnrollment::STATUS_REJECTED)
                                            <span class="sg-badge sg-badge-danger">Rifiutata</span>
                                            @break
                                    @endswitch
                                </td>
                                <td>
                                    @if($attempt)
                                        <strong>{{ $attempt->score }}</strong>
                                        <span class="sg-text-muted">/ {{ $attempt->total_questions }}</span>
                                    @else
                                        <span class="sg-text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($attempt && $total > 0)
                                        {{ number_format(($attempt->score / $total) * 100, 1) }}%
                                    @else
                                        <span class="sg-text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if(!$attempt)
                                        <span class="sg-badge sg-badge-warning">Non svolto</span>
                                    @elseif($passed)
                                        <span class="sg-badge sg-badge-success">Promosso</span>
                                    @else
                                        <span class="sg-badge sg-badge-danger">Rimandato</span>
                                    @endif
                                </td>
                                <td class="sg-text-muted">
                                    {{ $attempt?->created_at?->format('d/m/Y H:i') ?? '—' }}
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
