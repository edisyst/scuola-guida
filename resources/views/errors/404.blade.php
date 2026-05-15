@extends('errors.layout')

@section('theme-vars')
<style>
    :root {
        --error-color: #4cc9f0;
        --error-glow-rgb: 76, 201, 240;
        --error-gradient: linear-gradient(135deg, #4361ee, #4cc9f0);
    }
</style>
@endsection

@section('code', '404')
@section('icon', 'fa-compass')
@section('title', 'Pagina non trovata')
@section('message', 'La pagina che stai cercando non esiste o è stata spostata.<br>Controlla l\'URL o usa i pulsanti qui sotto.')
