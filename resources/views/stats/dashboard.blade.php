@extends('layouts.admin')

@section('title', $isAdminView ? "Statistiche di {$user->name}" : 'Le mie statistiche')

@section('content_header')@endsection

@section('content')
<div class="sg-wrapper">

    <div class="sg-header sg-flex-between">
        <div>
            <p class="sg-header-subtitle">
                @if($isAdminView)
                    Dashboard statistiche — vista admin
                @else
                    La tua dashboard personale
                @endif
            </p>
            <h1 class="sg-header-title">
                <i class="fas fa-chart-line mr-2"></i>
                @if($isAdminView)
                    Statistiche di {{ $user->name }}
                @else
                    Le mie statistiche
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
            <form method="POST" action="{{ route('stats.refresh', $user) }}" style="display:inline;">
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

    @if($stats['total_attempts'] === 0)
        <div class="sg-card sg-mt-3">
            <div class="sg-card-body sg-text-center" style="padding:48px;">
                <i class="fas fa-inbox" style="font-size:48px;color:#adb5bd;"></i>
                <h3 class="sg-mt-2">Nessun tentativo registrato</h3>
                <p class="sg-text-muted">
                    @if($isAdminView)
                        Questo utente non ha ancora completato nessun quiz.
                    @else
                        Inizia a giocare un quiz per vedere le tue statistiche.
                    @endif
                </p>
                @unless($isAdminView)
                    <a href="{{ route('quiz.random') }}" class="sg-btn sg-btn-success sg-mt-2">
                        <i class="fas fa-play"></i> Inizia un quiz
                    </a>
                @endunless
            </div>
        </div>
    @else

        {{-- KPI --}}
        <div class="row" style="margin:0 -8px;">

            <div class="col-lg-3 col-6" style="padding:0 8px;margin-bottom:16px;">
                <div class="sg-stat-card">
                    <div class="sg-stat-icon grad-blue"><i class="fas fa-clipboard-check"></i></div>
                    <div>
                        <div class="sg-stat-value">{{ $stats['total_attempts'] }}</div>
                        <div class="sg-stat-label">Tentativi totali</div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6" style="padding:0 8px;margin-bottom:16px;">
                <div class="sg-stat-card">
                    <div class="sg-stat-icon grad-green"><i class="fas fa-percentage"></i></div>
                    <div>
                        <div class="sg-stat-value">{{ $stats['avg_percentage'] }}%</div>
                        <div class="sg-stat-label">Media risultato</div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6" style="padding:0 8px;margin-bottom:16px;">
                <div class="sg-stat-card">
                    <div class="sg-stat-icon grad-orange"><i class="fas fa-trophy"></i></div>
                    <div>
                        <div class="sg-stat-value">{{ $stats['best_percentage'] }}%</div>
                        <div class="sg-stat-label">Miglior risultato</div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6" style="padding:0 8px;margin-bottom:16px;">
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
        <div class="row" style="margin:0 -8px;">

            <div class="col-lg-4 col-md-6" style="padding:0 8px;margin-bottom:16px;">
                <div class="sg-stat-card">
                    <div class="sg-stat-icon grad-blue"><i class="fas fa-stopwatch"></i></div>
                    <div>
                        <div class="sg-stat-value">{{ gmdate('i:s', $stats['avg_duration']) }}</div>
                        <div class="sg-stat-label">Durata media</div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6" style="padding:0 8px;margin-bottom:16px;">
                <div class="sg-stat-card">
                    <div class="sg-stat-icon grad-green"><i class="fas fa-check"></i></div>
                    <div>
                        <div class="sg-stat-value">{{ $stats['total_correct'] }}/{{ $stats['total_questions'] }}</div>
                        <div class="sg-stat-label">Risposte corrette / totali</div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6" style="padding:0 8px;margin-bottom:16px;">
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
        <div class="row" style="margin:0 -8px;">

            <div class="col-md-7" style="padding:0 8px;margin-bottom:16px;">
                <div class="sg-card">
                    <div class="sg-card-header">
                        <h2 class="sg-card-header-title">Andamento — ultimi 30 giorni</h2>
                    </div>
                    <div class="sg-card-body">
                        <canvas id="dailyChart" height="120"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-md-5" style="padding:0 8px;margin-bottom:16px;">
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
                                <th style="text-align:right;">Tentativi</th>
                                <th style="text-align:right;">Media %</th>
                                <th style="text-align:right;">Miglior %</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stats['avg_by_quiz'] as $row)
                                <tr>
                                    <td>{{ $row['title'] }}</td>
                                    <td style="text-align:right;">{{ $row['attempts'] }}</td>
                                    <td style="text-align:right;">
                                        <span class="sg-badge {{ $row['avg_pct'] >= 60 ? 'sg-badge-success' : 'sg-badge-warning' }}">
                                            {{ $row['avg_pct'] }}%
                                        </span>
                                    </td>
                                    <td style="text-align:right;">{{ $row['best_pct'] }}%</td>
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
                                <th style="text-align:right;">Punteggio</th>
                                <th style="text-align:right;">%</th>
                                <th>Esito</th>
                                <th style="text-align:right;">Durata</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stats['latest_attempts'] as $a)
                                <tr>
                                    <td>{{ $a['quiz_title'] }}</td>
                                    <td class="sg-text-muted">
                                        {{ \Illuminate\Support\Carbon::parse($a['created_at'])->format('d/m/Y H:i') }}
                                    </td>
                                    <td style="text-align:right;">{{ $a['score'] }}/{{ $a['total_questions'] }}</td>
                                    <td style="text-align:right;"><strong>{{ $a['percentage'] }}%</strong></td>
                                    <td>
                                        @if($a['is_passed'])
                                            <span class="sg-badge sg-badge-success">Superato</span>
                                        @else
                                            <span class="sg-badge sg-badge-danger">Non superato</span>
                                        @endif
                                    </td>
                                    <td style="text-align:right;" class="sg-text-muted">
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
