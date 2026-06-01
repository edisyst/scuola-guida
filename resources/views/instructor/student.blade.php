@extends('layouts.admin')

@section('title', 'Progressi — ' . $student->name)
@section('content_header')@endsection

@section('content')
<div class="sg-wrapper">

    {{-- Banner sola lettura --}}
    <div class="alert alert-info mb-3">
        <i class="fas fa-eye mr-1"></i>
        <strong>Visualizzazione in sola lettura.</strong>
        Stai consultando i progressi di <strong>{{ $student->name }}</strong> come istruttore.
        Nessuna modifica è possibile da questa vista.
    </div>

    <div class="sg-header sg-flex-between">
        <div>
            <h1 class="sg-header-title">
                <i class="fas fa-user-graduate mr-2"></i> {{ $student->name }}
            </h1>
            <p class="sg-header-subtitle sg-mt-1">{{ $student->email }}</p>
        </div>
        <div class="sg-header-actions">
            <a href="{{ route('instructor.students.index') }}" class="sg-btn sg-btn-light sg-btn-sm">
                <i class="fas fa-arrow-left"></i> Torna ai miei studenti
            </a>
        </div>
    </div>

    @php
        $stats  = $progress['stats'];
        $streak = $progress['streak'];
        $badges = $progress['badges'];
    @endphp

    {{-- Small-box KPI --}}
    <div class="row">
        <div class="col-sm-3">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $stats['total_attempts'] }}</h3>
                    <p>Tentativi totali</p>
                </div>
                <div class="icon"><i class="fas fa-clipboard-list"></i></div>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $stats['pass_rate'] }}%</h3>
                    <p>Tasso superamento</p>
                </div>
                <div class="icon"><i class="fas fa-check-circle"></i></div>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $streak['current'] }}</h3>
                    <p>Streak attuale</p>
                </div>
                <div class="icon"><i class="fas fa-fire"></i></div>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ count($badges) }}</h3>
                    <p>Badge guadagnati</p>
                </div>
                <div class="icon"><i class="fas fa-award"></i></div>
            </div>
        </div>
    </div>

    {{-- Statistiche dettaglio --}}
    <div class="row">
        <div class="col-md-5">
            <div class="sg-card mb-3">
                <div class="sg-card-header">
                    <h3 class="sg-card-title"><i class="fas fa-chart-bar mr-1"></i> Riepilogo statistiche</h3>
                </div>
                <table class="table table-sm mb-0">
                    <tbody>
                        <tr><th>Tentativi totali</th><td>{{ $stats['total_attempts'] }}</td></tr>
                        <tr><th>Domande risposte</th><td>{{ $stats['total_questions'] }}</td></tr>
                        <tr><th>Risposte corrette</th><td>{{ $stats['total_correct'] }}</td></tr>
                        <tr><th>Media percentuale</th><td>{{ $stats['avg_percentage'] }}%</td></tr>
                        <tr><th>Migliore risultato</th><td>{{ $stats['best_percentage'] }}%</td></tr>
                        <tr><th>Superati / Falliti</th><td>{{ $stats['passed_count'] }} / {{ $stats['failed_count'] }}</td></tr>
                        <tr><th>Streak più lungo</th><td>{{ $streak['longest'] }} giorni</td></tr>
                        <tr>
                            <th>Attivo oggi</th>
                            <td>
                                @if($streak['has_today'])
                                    <span class="sg-badge sg-badge-success"><i class="fas fa-check"></i> Sì</span>
                                @else
                                    <span class="sg-badge sg-badge-secondary">No</span>
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Badge --}}
            <div class="sg-card">
                <div class="sg-card-header">
                    <h3 class="sg-card-title"><i class="fas fa-award mr-1"></i> Badge</h3>
                </div>
                @if(empty($badges))
                    <div class="text-center py-3">
                        <p class="text-muted mb-0">Nessun badge ancora guadagnato.</p>
                    </div>
                @else
                    <div class="p-3">
                        @foreach($badges as $badge)
                            <span class="sg-badge sg-badge-warning mr-1 mb-1">
                                <i class="fas fa-award mr-1"></i>
                                {{ $badge['badge_code'] }}
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Ultimi tentativi --}}
        <div class="col-md-7">
            <div class="sg-card">
                <div class="sg-card-header">
                    <h3 class="sg-card-title"><i class="fas fa-history mr-1"></i> Ultimi tentativi</h3>
                </div>
                @if(empty($stats['latest_attempts']))
                    <div class="text-center py-4">
                        <p class="text-muted mb-0">Nessun tentativo registrato.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="sg-table">
                            <thead>
                                <tr>
                                    <th>Quiz</th>
                                    <th>Punteggio</th>
                                    <th>%</th>
                                    <th>Esito</th>
                                    <th>Data</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($stats['latest_attempts'] as $attempt)
                                    <tr>
                                        <td>{{ $attempt['quiz_title'] }}</td>
                                        <td>{{ $attempt['score'] }}/{{ $attempt['total_questions'] }}</td>
                                        <td>{{ $attempt['percentage'] }}%</td>
                                        <td>
                                            @if($attempt['is_passed'])
                                                <span class="sg-badge sg-badge-success">Superato</span>
                                            @else
                                                <span class="sg-badge sg-badge-danger">Non superato</span>
                                            @endif
                                        </td>
                                        <td class="sg-text-muted">
                                            {{ $attempt['created_at']
                                                ? \Carbon\Carbon::parse($attempt['created_at'])->format('d/m/Y H:i')
                                                : '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

</div>
@endsection
