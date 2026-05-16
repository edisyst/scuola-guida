@extends('layouts.admin')

@section('title', 'Statistiche')

@section('content_header')@endsection

@section('content')
<div class="sg-wrapper">

    <div class="sg-header">
        <p class="sg-header-subtitle">Panoramica</p>
        <h1 class="sg-header-title">Statistiche</h1>
    </div>

    {{-- KPI BOX --}}
    <div class="row sg-grid-row">

        <div class="col-lg-3 col-6 sg-grid-col">
            <div class="sg-stat-card">
                <div class="sg-stat-icon grad-blue"><i class="fas fa-users"></i></div>
                <div>
                    <div class="sg-stat-value">{{ $stats['users'] }}</div>
                    <div class="sg-stat-label">Utenti</div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6 sg-grid-col">
            <div class="sg-stat-card">
                <div class="sg-stat-icon grad-green"><i class="fas fa-question-circle"></i></div>
                <div>
                    <div class="sg-stat-value">{{ $stats['questions'] }}</div>
                    <div class="sg-stat-label">Domande</div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6 sg-grid-col">
            <div class="sg-stat-card">
                <div class="sg-stat-icon grad-orange"><i class="fas fa-folder-open"></i></div>
                <div>
                    <div class="sg-stat-value">{{ $stats['categories'] }}</div>
                    <div class="sg-stat-label">Categorie</div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6 sg-grid-col">
            <div class="sg-stat-card">
                <div class="sg-stat-icon grad-red"><i class="fas fa-clipboard-list"></i></div>
                <div>
                    <div class="sg-stat-value">{{ $stats['quizzes'] }}</div>
                    <div class="sg-stat-label">Quiz</div>
                </div>
            </div>
        </div>

    </div>

    {{-- GRAFICI --}}
    <div class="row sg-grid-row">

        <div class="col-md-6 sg-grid-col">
            <div class="sg-card">
                <div class="sg-card-header">
                    <h2 class="sg-card-header-title">Domande ultimi 30 giorni</h2>
                </div>
                <div class="sg-card-body">
                    <canvas id="questionsChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-6 sg-grid-col">
            <div class="sg-card">
                <div class="sg-card-header">
                    <h2 class="sg-card-header-title">Utenti ultimi 30 giorni</h2>
                </div>
                <div class="sg-card-body">
                    <canvas id="usersChart"></canvas>
                </div>
            </div>
        </div>

    </div>

</div>
@endsection

@section('js')
@parent

{{-- Chart.js CDN --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    const questionsData = @json($questionsChart);
    const usersData = @json($usersChart);

    function buildChart(canvasId, data, label, color) {
        new Chart(document.getElementById(canvasId), {
            type: 'line',
            data: {
                labels: data.map(i => i.date),
                datasets: [{
                    label: label,
                    data: data.map(i => i.total),
                    tension: 0.35,
                    borderColor: color,
                    backgroundColor: color + '22',
                    fill: true,
                    pointRadius: 3,
                    pointBackgroundColor: color,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: true, position: 'bottom' }
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f1f3f5' } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    buildChart('questionsChart', questionsData, 'Domande', '#28a745');
    buildChart('usersChart', usersData, 'Utenti', '#4361ee');
</script>

@endsection
