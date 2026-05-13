@extends('layouts.admin')

@section('title', 'Modifica utente')
@section('header', 'Modifica utente')
@section('content_header')@endsection

@section('content')
<div class="user-form-wrapper">

    <div class="user-page-header mb-4">
        <div>
            <h1 class="user-page-title">
                <i class="fas fa-user-edit mr-2"></i> Modifica utente
            </h1>
            <div class="user-page-subtitle">{{ $user->name }} &mdash; {{ $user->email }}</div>
        </div>
        <a href="{{ route('admin.users.index') }}" class="btn btn-light">
            <i class="fas fa-arrow-left mr-1"></i> Indietro
        </a>
    </div>

    <form method="POST" action="{{ route('admin.users.update', $user) }}">
        @csrf
        @method('PUT')

        @include('admin.users.form')

        <div class="text-end mt-3 mb-5 text-right">
            <button class="btn btn-submit-user">
                <i class="fas fa-save mr-1"></i> Salva modifiche
            </button>
        </div>
    </form>
</div>
@endsection

@push('css')
<style>
    .user-page-header {
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
        border-radius: 12px;
        padding: 22px 28px;
        color: #fff;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,.12);
    }
    .user-page-title {
        font-size: 1.4rem;
        font-weight: 700;
        margin: 0;
        letter-spacing: .3px;
    }
    .user-page-subtitle {
        color: rgba(255,255,255,.7);
        font-size: .85rem;
        margin-top: 4px;
    }
</style>
@endpush
