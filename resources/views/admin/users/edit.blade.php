@extends('layouts.admin')

@section('title', 'Modifica utente')
@section('content_header')@endsection

@section('content')
<div class="sg-wrapper-fluid">

    <div class="sg-header sg-flex-between">
        <div>
            <h1 class="sg-header-title"><i class="fas fa-user-edit mr-2"></i> Modifica utente</h1>
            <p class="sg-header-subtitle sg-mt-1">{{ $user->name }} &mdash; {{ $user->email }}</p>
        </div>
        <a href="{{ route('admin.users.index') }}" class="sg-btn sg-btn-light sg-btn-sm">
            <i class="fas fa-arrow-left"></i> Indietro
        </a>
    </div>

    <form method="POST" action="{{ route('admin.users.update', $user) }}">
        @csrf
        @method('PUT')

        @include('admin.users.form')

        <div class="sg-text-center sg-mt-3 sg-mb-3">
            <button class="sg-btn sg-btn-primary">
                <i class="fas fa-save"></i> Salva modifiche
            </button>
        </div>
    </form>

    <div class="sg-text-center sg-mt-2 sg-mb-3">
        <a href="{{ route('admin.users.download-data', $user) }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-download mr-1"></i> Esporta dati utente (GDPR art. 20)
        </a>
    </div>
</div>
@endsection
