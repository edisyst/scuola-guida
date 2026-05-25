@extends('layouts.admin')

@section('title', 'Test diagnostico')

@section('content_header')@endsection

@section('content')
<div class="sg-wrapper">

    <div class="sg-header">
        <p class="sg-header-subtitle">Punto di partenza personalizzato</p>
        <h1 class="sg-header-title">
            <i class="fas fa-stethoscope mr-2"></i> Test diagnostico
        </h1>
    </div>

    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-7">

            <div class="alert alert-info mb-4">
                <i class="fas fa-info-circle mr-1"></i>
                Rispondi a una breve serie di domande, una per categoria.
                Useremo le tue risposte per costruire un piano di studio personalizzato.
                Non c'è penalità: questo non è un esame.
            </div>

            <livewire:diagnostic-test />

        </div>
    </div>

</div>
@endsection
