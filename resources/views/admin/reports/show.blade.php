@extends('layouts.admin')

@section('title', 'Report periodico')
@section('content_header')@endsection

@section('content')
<div class="sg-wrapper-fluid">

    {{-- Header --}}
    <div class="sg-header sg-flex-between">
        <div>
            <p class="sg-header-subtitle">
                {{ $from->format('d/m/Y') }} — {{ $to->format('d/m/Y') }}
                @if($compare)
                    <span class="sg-badge sg-badge-info ml-1">Confronto attivo</span>
                @endif
            </p>
            <h1 class="sg-header-title"><i class="fas fa-chart-pie mr-2"></i> Report periodico</h1>
        </div>
        <div class="sg-header-actions">
            <a href="{{ route('admin.reports.index') }}" class="sg-btn sg-btn-light sg-btn-sm">
                <i class="fas fa-arrow-left"></i> Modifica filtri
            </a>
            <a href="{{ route('admin.reports.export-pdf', array_merge($filters, ['compare' => $compare ? '1' : ''])) }}"
               class="sg-btn sg-btn-danger sg-btn-sm">
                <i class="fas fa-file-pdf"></i> Esporta PDF
            </a>
        </div>
    </div>

    {{-- KPI Small Boxes --}}
    @php $c = $current; @endphp
    <div class="row mb-3">

        <div class="col-lg-3 col-6">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ $c['total_attempts'] }}</h3>
                    @if($compare && isset($delta['total_attempts']))
                        @include('admin.reports._delta', ['v' => $delta['total_attempts']])
                    @endif
                    <p>Tentativi completati</p>
                </div>
                <div class="icon"><i class="fas fa-clipboard-check"></i></div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $c['active_students'] }}</h3>
                    @if($compare && isset($delta['active_students']))
                        @include('admin.reports._delta', ['v' => $delta['active_students']])
                    @endif
                    <p>Studenti attivi</p>
                </div>
                <div class="icon"><i class="fas fa-users"></i></div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box {{ ($c['pass_rate'] ?? 0) >= 60 ? 'bg-success' : 'bg-warning' }}">
                <div class="inner">
                    <h3>{{ $c['pass_rate'] !== null ? number_format($c['pass_rate'], 1) . '%' : '—' }}</h3>
                    @if($compare && isset($delta['pass_rate']))
                        @include('admin.reports._delta', ['v' => $delta['pass_rate']])
                    @endif
                    <p>Tasso di promozione</p>
                </div>
                <div class="icon"><i class="fas fa-trophy"></i></div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-teal">
                <div class="inner">
                    <h3>{{ $c['average_score'] !== null ? number_format($c['average_score'], 1) . '%' : '—' }}</h3>
                    @if($compare && isset($delta['average_score']))
                        @include('admin.reports._delta', ['v' => $delta['average_score']])
                    @endif
                    <p>Punteggio medio</p>
                </div>
                <div class="icon"><i class="fas fa-chart-bar"></i></div>
            </div>
        </div>

    </div>

    {{-- Iscrizioni box --}}
    <div class="row mb-3">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-secondary">
                <div class="inner">
                    <h3>{{ $c['enrollments_count'] }}</h3>
                    <p>Iscrizioni approvate</p>
                </div>
                <div class="icon"><i class="fas fa-user-check"></i></div>
            </div>
        </div>
    </div>

    {{-- Confronto periodo precedente --}}
    @if($compare && isset($previous))
    <div class="sg-card mb-3">
        <div class="sg-card-header">
            <h2 class="sg-card-header-title">Confronto periodo precedente</h2>
            <small class="sg-text-muted">
                {{ $period['prev_from']->format('d/m/Y') }} — {{ $period['prev_to']->format('d/m/Y') }}
            </small>
        </div>
        <div class="table-responsive">
            <table class="table table-sm sg-table mb-0">
                <thead>
                    <tr>
                        <th>Metrica</th>
                        <th>Periodo corrente</th>
                        <th>Periodo precedente</th>
                        <th>Delta</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Tentativi completati</td>
                        <td><strong>{{ $current['total_attempts'] }}</strong></td>
                        <td>{{ $previous['total_attempts'] }}</td>
                        <td>@include('admin.reports._delta-cell', ['v' => $delta['total_attempts'] ?? null])</td>
                    </tr>
                    <tr>
                        <td>Studenti attivi</td>
                        <td><strong>{{ $current['active_students'] }}</strong></td>
                        <td>{{ $previous['active_students'] }}</td>
                        <td>@include('admin.reports._delta-cell', ['v' => $delta['active_students'] ?? null])</td>
                    </tr>
                    <tr>
                        <td>Tasso di promozione</td>
                        <td><strong>{{ $current['pass_rate'] !== null ? number_format($current['pass_rate'], 1) . '%' : '—' }}</strong></td>
                        <td>{{ $previous['pass_rate'] !== null ? number_format($previous['pass_rate'], 1) . '%' : '—' }}</td>
                        <td>@include('admin.reports._delta-cell', ['v' => $delta['pass_rate'] ?? null])</td>
                    </tr>
                    <tr>
                        <td>Punteggio medio</td>
                        <td><strong>{{ $current['average_score'] !== null ? number_format($current['average_score'], 1) . '%' : '—' }}</strong></td>
                        <td>{{ $previous['average_score'] !== null ? number_format($previous['average_score'], 1) . '%' : '—' }}</td>
                        <td>@include('admin.reports._delta-cell', ['v' => $delta['average_score'] ?? null])</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Grafici --}}
    <div class="row sg-grid-row">

        <div class="col-12 col-md-8 sg-grid-col">
            <div class="sg-card">
                <div class="sg-card-header">
                    <h2 class="sg-card-header-title">Trend tentativi giornalieri</h2>
                </div>
                <div class="sg-card-body">
                    @if(count($c['attempts_per_day']) > 0)
                        <canvas id="trendChart" height="90"></canvas>
                    @else
                        <p class="sg-text-muted">Nessun dato nel periodo selezionato.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4 sg-grid-col">
            <div class="sg-card">
                <div class="sg-card-header">
                    <h2 class="sg-card-header-title">Esiti per categoria</h2>
                </div>
                <div class="sg-card-body">
                    @if(count($c['outcomes_by_category']) > 0)
                        <canvas id="categoryChart" height="200"></canvas>
                    @else
                        <p class="sg-text-muted">Nessun dato.</p>
                    @endif
                </div>
            </div>
        </div>

    </div>

    {{-- Tabella esiti per categoria --}}
    <div class="sg-card mt-3">
        <div class="sg-card-header">
            <h2 class="sg-card-header-title">Distribuzione risposte per categoria</h2>
        </div>
        @if(count($c['outcomes_by_category']) > 0)
        <div class="table-responsive">
            <table class="table table-hover sg-table mb-0">
                <thead>
                    <tr>
                        <th>Categoria</th>
                        <th class="text-right">Corrette</th>
                        <th class="text-right">Sbagliate</th>
                        <th class="text-right">Totale</th>
                        <th class="text-right">% Successo</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($c['outcomes_by_category'] as $cat)
                        @php $tot = $cat['correct'] + $cat['incorrect']; @endphp
                        <tr>
                            <td>{{ $cat['name'] }}</td>
                            <td class="text-right text-success">{{ number_format($cat['correct']) }}</td>
                            <td class="text-right text-danger">{{ number_format($cat['incorrect']) }}</td>
                            <td class="text-right">{{ number_format($tot) }}</td>
                            <td class="text-right">
                                @if($tot > 0)
                                    {{ number_format($cat['correct'] / $tot * 100, 1) }}%
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
            <div class="sg-card-body sg-text-muted">Nessun dato per il periodo selezionato.</div>
        @endif
    </div>

    {{-- Top 20 domande più sbagliate --}}
    <div class="sg-card mt-3">
        <div class="sg-card-header">
            <h2 class="sg-card-header-title">Top 20 domande più sbagliate</h2>
        </div>
        @if(count($c['most_failed_questions']) > 0)
        <div class="table-responsive">
            <table class="table table-hover sg-table mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Domanda</th>
                        <th>Categoria</th>
                        <th class="text-right">Errori</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($c['most_failed_questions'] as $i => $q)
                        <tr>
                            <td class="sg-text-muted">{{ $i + 1 }}</td>
                            <td>{{ Str::limit($q['question'], 120) }}</td>
                            <td><span class="sg-badge sg-badge-light">{{ $q['category'] }}</span></td>
                            <td class="text-right text-danger font-weight-bold">{{ number_format($q['errors']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
            <div class="sg-card-body sg-text-muted">Nessuna domanda sbagliata nel periodo selezionato.</div>
        @endif
    </div>

</div>
@endsection

@section('js')
@parent

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
(function () {
    var trendData   = @json($current['attempts_per_day']);
    var catData     = @json($current['outcomes_by_category']);

    // Grafico trend
    if (document.getElementById('trendChart') && trendData.length > 0) {
        new Chart(document.getElementById('trendChart'), {
            type: 'line',
            data: {
                labels: trendData.map(function (d) { return d.date; }),
                datasets: [{
                    label: 'Tentativi',
                    data: trendData.map(function (d) { return d.count; }),
                    tension: 0.35,
                    borderColor: '#4361ee',
                    backgroundColor: '#4361ee22',
                    fill: true,
                    pointRadius: 2,
                    pointBackgroundColor: '#4361ee',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f1f3f5' } },
                    x: { grid: { display: false }, ticks: { maxTicksLimit: 12 } }
                }
            }
        });
    }

    // Grafico categorie (bar orizzontale — top 10 per leggibilità)
    if (document.getElementById('categoryChart') && catData.length > 0) {
        var top = catData.slice(0, 10);
        new Chart(document.getElementById('categoryChart'), {
            type: 'bar',
            data: {
                labels: top.map(function (c) { return c.name.length > 20 ? c.name.slice(0, 20) + '…' : c.name; }),
                datasets: [
                    {
                        label: 'Corrette',
                        data: top.map(function (c) { return c.correct; }),
                        backgroundColor: '#28a74588',
                        borderColor: '#28a745',
                        borderWidth: 1
                    },
                    {
                        label: 'Sbagliate',
                        data: top.map(function (c) { return c.incorrect; }),
                        backgroundColor: '#dc354588',
                        borderColor: '#dc3545',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                plugins: { legend: { display: true, position: 'bottom' } },
                scales: {
                    x: { beginAtZero: true, stacked: false }
                }
            }
        });
    }
})();
</script>

@endsection
