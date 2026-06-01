@extends('layouts.admin')

@section('title', 'I miei studenti')
@section('content_header')@endsection

@section('content')
<div class="sg-wrapper">

    <div class="sg-header">
        <h1 class="sg-header-title"><i class="fas fa-user-graduate mr-2"></i> I miei studenti</h1>
        <p class="sg-header-subtitle sg-mt-1">Monitora i progressi degli studenti assegnati — sola lettura</p>
    </div>

    @if(empty($overview))
        <div class="sg-card">
            <div class="text-center py-5">
                <i class="fas fa-user-graduate fa-3x text-muted mb-3"></i>
                <p class="text-muted mb-1">Nessuno studente assegnato.</p>
                <p class="text-muted small">Contatta un amministratore per ricevere studenti da monitorare.</p>
            </div>
        </div>
    @else
        <div class="sg-card">
            <div class="table-responsive">
                <table class="sg-table">
                    <thead>
                        <tr>
                            <th>Studente</th>
                            <th>Ultimo tentativo</th>
                            <th>Ultimo punteggio</th>
                            <th>Streak</th>
                            <th>Attivo oggi</th>
                            <th class="text-right" style="width:100px;">Dettaglio</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($overview as $row)
                            <tr>
                                <td>
                                    <span class="sg-user-avatar">{{ strtoupper(substr($row['name'], 0, 1)) }}</span>
                                    <strong>{{ $row['name'] }}</strong>
                                    <br><small class="sg-text-muted">{{ $row['email'] }}</small>
                                </td>
                                <td class="sg-text-muted">
                                    {{ $row['last_attempt_at']
                                        ? \Carbon\Carbon::parse($row['last_attempt_at'])->diffForHumans()
                                        : '—' }}
                                </td>
                                <td>
                                    @if($row['last_score'])
                                        <span class="sg-badge {{ $row['last_score']['pct'] >= 60 ? 'sg-badge-success' : 'sg-badge-danger' }}">
                                            {{ $row['last_score']['pct'] }}%
                                        </span>
                                    @else
                                        <span class="sg-text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($row['streak'] > 0)
                                        <span class="sg-badge sg-badge-warning">
                                            <i class="fas fa-fire"></i> {{ $row['streak'] }}
                                        </span>
                                    @else
                                        <span class="sg-text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($row['active_today'])
                                        <i class="fas fa-circle text-success" title="Attivo oggi"></i>
                                    @else
                                        <i class="fas fa-circle text-secondary" title="Non attivo oggi"></i>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <a href="{{ route('instructor.students.show', $row['id']) }}"
                                       class="sg-btn sg-btn-primary sg-btn-sm">
                                        <i class="fas fa-eye"></i> Vedi
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
