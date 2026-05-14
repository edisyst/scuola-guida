@extends('layouts.admin')

@section('title', 'Modifica quiz')
@section('content_header')@endsection

@section('content')
<div class="sg-wrapper-sm">

    <div class="sg-header sg-flex-between">
        <div>
            <p class="sg-header-subtitle">Catalogo</p>
            <h1 class="sg-header-title"><i class="fas fa-edit mr-2"></i> Modifica quiz</h1>
        </div>
        <a href="{{ route('admin.quizzes.index') }}" class="sg-btn sg-btn-light sg-btn-sm">
            <i class="fas fa-arrow-left"></i> Indietro
        </a>
    </div>

    <form method="POST" action="{{ route('admin.quizzes.update', $quiz) }}">
        @csrf
        @method('PUT')

        <div class="sg-card">
            <div class="sg-card-body">
                @include('admin.quizzes.partials.form')
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
