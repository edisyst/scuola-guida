@extends('layouts.admin')

@section('title', 'Simulatore Esame')

@section('content_header')@endsection

@section('content')
<div class="sg-wrapper">

    <div class="sg-header">
        <p class="sg-header-subtitle">Simula l'esame ufficiale di teoria patente B</p>
        <h1 class="sg-header-title">
            <i class="fas fa-graduation-cap mr-2"></i> Simulatore Esame
        </h1>
    </div>

    <div class="card" style="max-width: 720px; margin: 0 auto;">
        <div class="card-body p-4">

            <p class="text-muted mb-4">
                Le domande vengono estratte dal database secondo la distribuzione
                ministeriale per categoria. Il formato riproduce l'esame ufficiale
                vigente dal 20 dicembre 2021. Questa simulazione è puramente didattica
                e <strong>non conta ai fini delle iscrizioni ufficiali</strong>.
            </p>

            <div class="row text-center mb-4">
                <div class="col-md-4 mb-3 mb-md-0">
                    <div class="info-box bg-info">
                        <span class="info-box-icon"><i class="fas fa-question-circle"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Domande</span>
                            <span class="info-box-number">{{ $questions }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3 mb-md-0">
                    <div class="info-box bg-warning">
                        <span class="info-box-icon"><i class="fas fa-clock"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Tempo</span>
                            <span class="info-box-number">{{ $timeLimit }} min</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-box bg-danger">
                        <span class="info-box-icon"><i class="fas fa-times-circle"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Max errori</span>
                            <span class="info-box-number">{{ $maxErrors }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="alert alert-secondary small mb-4">
                <i class="fas fa-info-circle me-1"></i>
                Una risposta non data viene conteggiata come errore al momento della
                consegna. Puoi navigare liberamente tra le domande e tornare indietro
                a modificare le risposte.
            </div>

            <form action="{{ route('simulator.start') }}" method="POST" class="text-center">
                @csrf
                <button type="submit" class="btn btn-lg btn-primary">
                    <i class="fas fa-play me-1"></i> Inizia simulazione
                </button>
                <div class="mt-3">
                    <a href="{{ route('dashboard') }}" class="text-muted small">
                        <i class="fas fa-arrow-left me-1"></i> Torna alla dashboard
                    </a>
                </div>
            </form>

        </div>
    </div>

</div>
@endsection
