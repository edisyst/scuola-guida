@extends('layouts.admin')

@section('title', $isAdminView ? "Statistiche di {$user->name}" : 'Dashboard')

@section('content_header')@endsection

@section('content')
<div class="sg-wrapper">

    <div class="sg-header sg-flex-between">
        <div>
            <p class="sg-header-subtitle">
                @if($isAdminView)
                    Statistiche — vista admin
                @else
                    La tua dashboard personale
                @endif
            </p>
            <h1 class="sg-header-title">
                <i class="fas fa-chart-line mr-2"></i>
                @if($isAdminView)
                    Statistiche di {{ $user->name }}
                @else
                    Dashboard
                @endif
            </h1>
            <p class="sg-text-muted sg-mt-1">
                <i class="far fa-clock"></i>
                Aggiornato:
                {{ \Illuminate\Support\Carbon::parse($stats['generated_at'])->diffForHumans() }}
                — cache {{ \App\Services\UserStatsService::CACHE_TTL / 60 }} min
            </p>
        </div>
        <div class="sg-header-actions">
            @if($isAdminView)
                <a href="{{ route('admin.users.index') }}" class="sg-btn sg-btn-light sg-btn-sm">
                    <i class="fas fa-arrow-left"></i> Torna agli utenti
                </a>
            @endif
            <form method="POST" action="{{ route('dashboard.refresh', $user) }}" class="d-inline">
                @csrf
                @if($isAdminView)
                    <input type="hidden" name="as_admin" value="1">
                @endif
                <button type="submit" class="sg-btn sg-btn-primary sg-btn-sm">
                    <i class="fas fa-sync-alt"></i> Aggiorna ora
                </button>
            </form>
        </div>
    </div>

    @if(!$isAdminView && !empty($nextSession))
        <div class="info-box {{ $nextSession->enrollment_status === 'upcoming' ? 'bg-gradient-warning' : 'bg-gradient-success' }} mb-3"
             @if($nextSession->enrollment_status === 'upcoming' && $nextSession->enrollments_open_at)
             x-data="countdown({{ $nextSession->enrollments_open_at->timestamp }})" x-init="start()"
             @endif
        >
            <span class="info-box-icon"><i class="fas fa-calendar-{{ $nextSession->enrollment_status === 'upcoming' ? 'alt' : 'check' }}"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Prossima sessione</span>
                <span class="info-box-number">{{ $nextSession->title }}</span>
                <span class="progress-description">
                    @if(in_array($nextSession->enrollment_status, ['open', 'not_scheduled']))
                        Iscrizioni aperte
                        @if($nextSession->enrollments_close_at)
                            fino al {{ $nextSession->enrollments_close_at->format('d/m/Y') }}
                        @endif
                    @elseif($nextSession->enrollment_status === 'upcoming' && $nextSession->enrollments_open_at)
                        Apre tra: <strong x-text="display">{{ $nextSession->enrollments_open_at->format('d/m/Y H:i') }}</strong>
                    @endif
                    &mdash; <a href="{{ route('calendar.index') }}" class="{{ $nextSession->enrollment_status === 'upcoming' ? 'text-dark' : 'text-white' }}"><u>Vedi calendario</u></a>
                </span>
            </div>
        </div>
    @endif

    @if(!$isAdminView && !empty($reviewErrorsCount))
        <a href="{{ route('viewer.review-errors.index') }}"
           class="info-box bg-gradient-warning mb-3 text-dark"
           style="text-decoration:none;">
            <span class="info-box-icon"><i class="fas fa-exclamation-triangle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Errori da rivedere</span>
                <span class="info-box-number">{{ $reviewErrorsCount }}</span>
                <span class="progress-description">domande con risposte sbagliate &mdash; clicca per rivedere</span>
            </div>
        </a>
    @endif

    @if(!$isAdminView && ($dueToday ?? 0) > 0)
        <a href="{{ route('viewer.smart-review.session') }}"
           class="info-box bg-gradient-primary mb-3 text-white"
           style="text-decoration:none;">
            <span class="info-box-icon"><i class="fas fa-brain"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Ripasso intelligente</span>
                <span class="info-box-number">{{ $dueToday }}</span>
                <span class="progress-description">domande da ripassare oggi &mdash; clicca per iniziare</span>
            </div>
        </a>
    @endif

    @if(!$isAdminView && isset($currentStreak))
        @php
            $hasActivityToday = $currentStreak > 0 && isset($activityToday) && $activityToday;
            $atRisk = ($currentStreak > 0) && !($activityToday ?? false);
        @endphp
        <a href="{{ route('viewer.profile.badges') }}"
           class="info-box {{ $currentStreak > 0 ? 'bg-gradient-warning' : 'bg-light border' }} mb-3 text-{{ $currentStreak > 0 ? 'dark' : 'muted' }}"
           style="text-decoration:none;">
            <span class="info-box-icon">
                <i class="fas fa-fire{{ $currentStreak === 0 ? '-alt' : '' }}"></i>
            </span>
            <div class="info-box-content">
                <span class="info-box-text">La tua streak</span>
                <span class="info-box-number">
                    @if($currentStreak === 0)
                        0 giorni
                    @else
                        {{ $currentStreak }} {{ $currentStreak === 1 ? 'giorno' : 'giorni' }}
                        @if($atRisk)
                            <span class="badge badge-danger ml-2" style="font-size:0.7rem;">A rischio</span>
                        @endif
                    @endif
                </span>
                <span class="progress-description">
                    @if($currentStreak === 0)
                        Inizia oggi! Studia qualcosa per avviare la tua streak.
                    @elseif($atRisk)
                        Non hai ancora studiato oggi &mdash; studia per non perdere la streak!
                    @else
                        Migliore di sempre: {{ $longestStreak }} {{ $longestStreak === 1 ? 'giorno' : 'giorni' }}
                    @endif
                    &mdash; <u>Vedi badge</u>
                </span>
            </div>
        </a>
    @endif

    @if(!$isAdminView && ($hasDiagnostic ?? false) === false && $stats['total_attempts'] === 0)
        <a href="{{ route('viewer.diagnostic.show') }}"
           class="info-box bg-gradient-info mb-3 text-white"
           style="text-decoration:none;">
            <span class="info-box-icon"><i class="fas fa-stethoscope"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Punto di partenza</span>
                <span class="info-box-number" style="font-size:1rem;">Inizia con un test diagnostico</span>
                <span class="progress-description">
                    Rispondi a una domanda per categoria e costruiamo il tuo piano di studio — clicca per iniziare
                </span>
            </div>
        </a>
    @endif

    @if($stats['total_attempts'] === 0)
        <div class="sg-card sg-mt-3">
            <div class="sg-card-body sg-text-center p-5">
                <i class="fas fa-inbox text-muted" style="font-size:48px;"></i>
                <h3 class="sg-mt-2">Nessun tentativo registrato</h3>
                <p class="sg-text-muted">
                    @if($isAdminView)
                        Questo utente non ha ancora completato nessun quiz.
                    @else
                        Inizia a giocare un quiz per vedere le tue statistiche.
                    @endif
                </p>
                @unless($isAdminView)
                    {{-- entry point quiz: catalogo dei quiz confermati per iscrizione --}}
                    <a href="{{ route('quiz.confirmed.index') }}" class="sg-btn sg-btn-success sg-mt-2">
                        <i class="fas fa-clipboard-list"></i> Scegli un quiz
                    </a>
                @endunless
            </div>
        </div>
    @else

        {{-- KPI --}}
        <div class="row sg-grid-row">

            <div class="col-lg-3 col-6 sg-grid-col">
                <div class="sg-stat-card">
                    <div class="sg-stat-icon grad-blue"><i class="fas fa-clipboard-check"></i></div>
                    <div>
                        <div class="sg-stat-value">{{ $stats['total_attempts'] }}</div>
                        <div class="sg-stat-label">Tentativi totali</div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6 sg-grid-col">
                <div class="sg-stat-card">
                    <div class="sg-stat-icon grad-green"><i class="fas fa-percentage"></i></div>
                    <div>
                        <div class="sg-stat-value">{{ $stats['avg_percentage'] }}%</div>
                        <div class="sg-stat-label">Media risultato</div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6 sg-grid-col">
                <div class="sg-stat-card">
                    <div class="sg-stat-icon grad-orange"><i class="fas fa-trophy"></i></div>
                    <div>
                        <div class="sg-stat-value">{{ $stats['best_percentage'] }}%</div>
                        <div class="sg-stat-label">Miglior risultato</div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6 sg-grid-col">
                <div class="sg-stat-card">
                    <div class="sg-stat-icon grad-red"><i class="fas fa-check-double"></i></div>
                    <div>
                        <div class="sg-stat-value">{{ $stats['pass_rate'] }}%</div>
                        <div class="sg-stat-label">
                            Tasso di superamento
                            <small class="sg-text-muted">({{ $stats['passed_count'] }}/{{ $stats['total_attempts'] }})</small>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- Riga 2: durata, risposte --}}
        <div class="row sg-grid-row">

            <div class="col-lg-4 col-md-6 sg-grid-col">
                <div class="sg-stat-card">
                    <div class="sg-stat-icon grad-blue"><i class="fas fa-stopwatch"></i></div>
                    <div>
                        <div class="sg-stat-value">{{ gmdate('i:s', $stats['avg_duration']) }}</div>
                        <div class="sg-stat-label">Durata media</div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6 sg-grid-col">
                <div class="sg-stat-card">
                    <div class="sg-stat-icon grad-green"><i class="fas fa-check"></i></div>
                    <div>
                        <div class="sg-stat-value">{{ $stats['total_correct'] }}/{{ $stats['total_questions'] }}</div>
                        <div class="sg-stat-label">Risposte corrette / totali</div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6 sg-grid-col">
                <div class="sg-stat-card">
                    <div class="sg-stat-icon grad-orange"><i class="fas fa-calendar-alt"></i></div>
                    <div>
                        <div class="sg-stat-value" style="font-size:1.2rem;">
                            {{ $stats['last_attempt_at'] ? \Illuminate\Support\Carbon::parse($stats['last_attempt_at'])->diffForHumans() : '—' }}
                        </div>
                        <div class="sg-stat-label">Ultimo tentativo</div>
                    </div>
                </div>
            </div>

        </div>

        {{-- Grafici --}}
        <div class="row sg-grid-row">

            <div class="col-12 col-md-7 sg-grid-col">
                <div class="sg-card">
                    <div class="sg-card-header">
                        <h2 class="sg-card-header-title">Andamento — ultimi 30 giorni</h2>
                    </div>
                    <div class="sg-card-body">
                        <canvas id="dailyChart" height="120"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-5 sg-grid-col">
                <div class="sg-card">
                    <div class="sg-card-header">
                        <h2 class="sg-card-header-title">Esiti</h2>
                    </div>
                    <div class="sg-card-body">
                        <canvas id="passChart" height="120"></canvas>
                    </div>
                </div>
            </div>

        </div>

        {{-- Tabella per-quiz --}}
        @if(!empty($stats['avg_by_quiz']))
            <div class="sg-card sg-mt-3">
                <div class="sg-card-header">
                    <h2 class="sg-card-header-title">Performance per quiz</h2>
                </div>
                <div class="table-responsive">
                    <table class="sg-table">
                        <thead>
                            <tr>
                                <th>Quiz</th>
                                <th class="text-right">Tentativi</th>
                                <th class="text-right">Media %</th>
                                <th class="text-right">Miglior %</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stats['avg_by_quiz'] as $row)
                                <tr>
                                    <td>{{ $row['title'] }}</td>
                                    <td class="text-right">{{ $row['attempts'] }}</td>
                                    <td class="text-right">
                                        <span class="sg-badge {{ $row['avg_pct'] >= 60 ? 'sg-badge-success' : 'sg-badge-warning' }}">
                                            {{ $row['avg_pct'] }}%
                                        </span>
                                    </td>
                                    <td class="text-right">{{ $row['best_pct'] }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- Ultimi tentativi --}}
        @if(!empty($stats['latest_attempts']))
            <div class="sg-card sg-mt-3">
                <div class="sg-card-header">
                    <h2 class="sg-card-header-title">Ultimi 10 tentativi</h2>
                </div>
                <div class="table-responsive">
                    <table class="sg-table">
                        <thead>
                            <tr>
                                <th>Quiz</th>
                                <th>Data</th>
                                <th class="text-right">Punteggio</th>
                                <th class="text-right">%</th>
                                <th>Esito</th>
                                <th class="text-right">Durata</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stats['latest_attempts'] as $a)
                                <tr>
                                    <td>{{ $a['quiz_title'] }}</td>
                                    <td class="sg-text-muted">
                                        {{ \Illuminate\Support\Carbon::parse($a['created_at'])->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="text-right">{{ $a['score'] }}/{{ $a['total_questions'] }}</td>
                                    <td class="text-right"><strong>{{ $a['percentage'] }}%</strong></td>
                                    <td>
                                        @if($a['is_passed'])
                                            <span class="sg-badge sg-badge-success">Superato</span>
                                        @else
                                            <span class="sg-badge sg-badge-danger">Non superato</span>
                                        @endif
                                    </td>
                                    <td class="text-right sg-text-muted">
                                        {{ $a['duration'] ? gmdate('i:s', $a['duration']) : '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

    @endif

</div>
@endsection

@section('js')
@parent

<script>
function countdown(targetTimestamp) {
    return {
        display: '',
        intervalId: null,
        start() {
            this.update();
            this.intervalId = setInterval(() => this.update(), 1000);
        },
        update() {
            const diff = targetTimestamp - Math.floor(Date.now() / 1000);
            if (diff <= 0) {
                this.display = 'Iscrizioni aperte';
                clearInterval(this.intervalId);
                return;
            }
            const d = Math.floor(diff / 86400);
            const h = Math.floor((diff % 86400) / 3600);
            const m = Math.floor((diff % 3600) / 60);
            this.display = d > 0 ? `${d}g ${h}h ${m}m` : `${h}h ${m}m`;
        }
    };
}
</script>

@if($stats['total_attempts'] > 0)
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const dailyData = @json($stats['daily_chart']);
    const passed = {{ $stats['passed_count'] }};
    const failed = {{ $stats['failed_count'] }};

    if (document.getElementById('dailyChart')) {
        new Chart(document.getElementById('dailyChart'), {
            type: 'line',
            data: {
                labels: dailyData.map(i => i.date),
                datasets: [
                    {
                        label: 'Media %',
                        data: dailyData.map(i => i.avg_pct),
                        tension: 0.35,
                        borderColor: '#4361ee',
                        backgroundColor: '#4361ee22',
                        fill: true,
                        yAxisID: 'y',
                        borderWidth: 2,
                        pointRadius: 3,
                    },
                    {
                        label: 'Tentativi',
                        data: dailyData.map(i => i.attempts),
                        type: 'bar',
                        backgroundColor: '#28a74566',
                        borderColor: '#28a745',
                        yAxisID: 'y1',
                        borderWidth: 1,
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: true, position: 'bottom' } },
                scales: {
                    y:  { beginAtZero: true, max: 100, position: 'left',  title: { display: true, text: 'Media %' } },
                    y1: { beginAtZero: true, position: 'right', title: { display: true, text: 'Tentativi' }, grid: { drawOnChartArea: false } },
                    x:  { grid: { display: false } }
                }
            }
        });
    }

    if (document.getElementById('passChart')) {
        new Chart(document.getElementById('passChart'), {
            type: 'doughnut',
            data: {
                labels: ['Superati', 'Non superati'],
                datasets: [{
                    data: [passed, failed],
                    backgroundColor: ['#28a745', '#dc3545'],
                    borderWidth: 0,
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: true, position: 'bottom' } }
            }
        });
    }
</script>
@endif

@endsection
