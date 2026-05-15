@extends('errors.layout')

@section('theme-vars')
<style>
    :root {
        --error-color: #ffc107;
        --error-glow-rgb: 255, 193, 7;
        --error-gradient: linear-gradient(135deg, #e67e22, #ffc107);
    }
</style>
@endsection

@section('code', '401')
@section('icon', 'fa-lock')
@section('title', 'Accesso non autorizzato')
@section('message', 'Devi effettuare l\'accesso per visualizzare questa pagina.<br>Esegui il login e riprova.')
