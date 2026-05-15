@extends('layouts.admin')

@section('title', 'Le mie iscrizioni')
@section('content_header')@endsection

@section('content')
<div class="sg-wrapper">

    <div class="sg-header">
        <p class="sg-header-subtitle">Storico personale</p>
        <h1 class="sg-header-title"><i class="fas fa-list-check mr-2"></i> Le mie iscrizioni</h1>
    </div>

    <div class="sg-card">
        @if($enrollments->isEmpty())
            <div class="sg-table-empty">Non hai ancora richiesto nessuna iscrizione.</div>
        @else
            <div class="table-responsive">
                <table class="sg-table">
                    <thead>
                        <tr>
                            <th>Quiz</th>
                            <th>Stato</th>
                            <th>Richiesta</th>
                            <th>Revisione</th>
                            <th style="text-align:right;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($enrollments as $enrollment)
                            <tr>
                                <td><strong>{{ $enrollment->quiz->title ?? '—' }}</strong></td>
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
                                    @if($enrollment->reviewed_at)
                                        {{ $enrollment->reviewed_at->format('d/m/Y H:i') }}
                                        <br><small>{{ $enrollment->reviewer->name ?? '' }}</small>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td style="text-align:right;">
                                    @if($enrollment->isApproved())
                                        <a href="{{ route('quiz.play', $enrollment->quiz) }}"
                                           class="sg-btn sg-btn-primary sg-btn-sm"
                                           onclick="return confirm('Puoi svolgere questo quiz una sola volta. Procedere?');">
                                            <i class="fas fa-play"></i> Svolgi
                                        </a>
                                    @endif
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
