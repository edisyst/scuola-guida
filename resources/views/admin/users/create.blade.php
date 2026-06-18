@extends('layouts.admin')

@section('title', 'Nuovo utente')
@section('content_header')@endsection

@section('content')
<div class="sg-wrapper-fluid">

    <div class="sg-header sg-flex-between">
        <div>
            <h1 class="sg-header-title"><i class="fas fa-user-plus mr-2"></i> Nuovo utente</h1>
            <p class="sg-header-subtitle sg-mt-1">Crea un nuovo account e assegna ruolo e permessi</p>
        </div>
        <a href="{{ route('admin.users.index') }}" class="sg-btn sg-btn-light sg-btn-sm">
            <i class="fas fa-arrow-left"></i> Indietro
        </a>
    </div>

    <form method="POST" action="{{ route('admin.users.store') }}">
        @csrf

        @include('admin.users.form')

        <div class="sg-text-center sg-mt-3 sg-mb-3">
            <button class="sg-btn sg-btn-primary">
                <i class="fas fa-check"></i> Crea utente
            </button>
        </div>
    </form>
</div>
@endsection
