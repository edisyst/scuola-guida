@extends('layouts.admin')

@section('title', 'Tutti i tentativi')
@section('content_header')@endsection

@section('content')
<div class="sg-wrapper">

    <div class="sg-header">
        <p class="sg-header-subtitle">Storico globale</p>
        <h1 class="sg-header-title"><i class="fas fa-history mr-2"></i> Tutti i tentativi</h1>
    </div>

    <div class="sg-card">
        @if($attempts->isEmpty())
            <div class="sg-table-empty">Nessun tentativo presente.</div>
        @else
            <div class="table-responsive">
                <table class="sg-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Utente</th>
                            <th>Quiz</th>
                            <th>Punteggio</th>
                            <th>%</th>
                            <th>Esito</th>
                            <th>Durata</th>
                            <th>Data</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($attempts as $attempt)
                            <tr>
                                <td class="sg-text-muted">{{ $attempt->id }}</td>
                                <td>{{ $attempt->user->name ?? '—' }}</td>
                                <td>{{ $attempt->quiz->title ?? '—' }}</td>
                                <td>
                                    <strong>{{ $attempt->score }}</strong>
                                    <span class="sg-text-muted">/ {{ $attempt->total_questions }}</span>
                                </td>
                                <td>{{ $attempt->percentage }}%</td>
                                <td>
                                    @if($attempt->is_passed)
                                        <span class="sg-badge sg-badge-success">Superato</span>
                                    @else
                                        <span class="sg-badge sg-badge-danger">Non superato</span>
                                    @endif
                                </td>
                                <td class="sg-text-muted">
                                    {{ $attempt->duration ? gmdate('i:s', $attempt->duration) : '—' }}
                                </td>
                                <td class="sg-text-muted">
                                    {{ $attempt->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td>
                                    <a href="{{ route('quiz.attempts.show', $attempt) }}"
                                       class="sg-btn sg-btn-outline sg-btn-sm">
                                        Dettaglio
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="sg-card-section">
                {{ $attempts->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
