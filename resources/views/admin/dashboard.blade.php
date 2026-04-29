@extends('layouts.admin')

@section('header', 'Dashboard')

@section('content')

{{-- KPI BOX --}}
<div class="row">

    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ $stats['users'] }}</h3>
                <p>Utenti</p>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ $stats['questions'] }}</h3>
                <p>Domande</p>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ $stats['categories'] }}</h3>
                <p>Categorie</p>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>{{ $stats['quizzes'] }}</h3>
                <p>Quiz</p>
            </div>
        </div>
    </div>

</div>

{{-- GRAFICI --}}
<div class="row mt-4">

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Domande ultimi 30 giorni</h3>
            </div>
            <div class="card-body">
                <canvas id="questionsChart"></canvas>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Utenti ultimi 30 giorni</h3>
            </div>
            <div class="card-body">
                <canvas id="usersChart"></canvas>
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

    function buildChart(canvasId, data, label) {

        new Chart(document.getElementById(canvasId), {
            type: 'line',
            data: {
                labels: data.map(i => i.date),
                datasets: [{
                    label: label,
                    data: data.map(i => i.total),
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: true
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    buildChart('questionsChart', questionsData, 'Domande');
    buildChart('usersChart', usersData, 'Utenti');

</script>

@endsection
