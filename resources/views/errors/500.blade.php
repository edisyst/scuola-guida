@extends('errors.layout')

@section('theme-vars')
<style>
    :root {
        --error-color: #ff7e00;
        --error-glow-rgb: 255, 126, 0;
        --error-gradient: linear-gradient(135deg, #ff7e00, #ffc107);
    }
</style>
@endsection

@section('code', '500')
@section('icon', 'fa-server')
@section('title', 'Errore interno del server')
@section('message', 'Si è verificato un problema imprevisto sul server.<br>Il nostro team è stato notificato. Riprova tra qualche minuto.')
