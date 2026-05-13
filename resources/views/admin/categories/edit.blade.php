@extends('layouts.admin')

@section('title', 'Modifica categoria')
@section('content_header')@endsection

@section('content')
<div class="sg-wrapper-sm">

    <div class="sg-header sg-flex-between">
        <div>
            <p class="sg-header-subtitle">Catalogo</p>
            <h1 class="sg-header-title"><i class="fas fa-edit mr-2"></i> Modifica categoria</h1>
        </div>
        <a href="{{ route('admin.categories.index') }}" class="sg-btn sg-btn-light sg-btn-sm">
            <i class="fas fa-arrow-left"></i> Indietro
        </a>
    </div>

    <form action="{{ route('admin.categories.update', $category) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="sg-card">
            <div class="sg-card-body">
                @include('admin.categories.partials.form')
            </div>
        </div>

        <div class="sg-mt-3 sg-text-center">
            <button class="sg-btn sg-btn-primary">
                <i class="fas fa-save"></i> Aggiorna
            </button>
        </div>
    </form>
</div>
@endsection
