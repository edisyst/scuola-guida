@extends('errors.layout')

@section('theme-vars')
<style>
    :root {
        --error-color: #dc3545;
        --error-glow-rgb: 220, 53, 69;
        --error-gradient: linear-gradient(135deg, #dc3545, #e83e8c);
    }
</style>
@endsection

@section('code', '403')
@section('icon', 'fa-ban')
@section('title', 'Accesso vietato')
@section('message', 'Non hai i permessi necessari per accedere a questa risorsa.<br>Contatta un amministratore se ritieni si tratti di un errore.')
