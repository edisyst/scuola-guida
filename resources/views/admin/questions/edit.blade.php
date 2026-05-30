@extends('layouts.admin')

@section('title', 'Modifica domanda')
@section('content_header')@endsection

@section('content')
<div class="sg-wrapper-sm">

    <div class="sg-header sg-flex-between">
        <div>
            <p class="sg-header-subtitle">Catalogo</p>
            <h1 class="sg-header-title"><i class="fas fa-edit mr-2"></i> Modifica domanda</h1>
        </div>
        <a href="{{ route('admin.questions.index') }}" class="sg-btn sg-btn-light sg-btn-sm">
            <i class="fas fa-arrow-left"></i> Indietro
        </a>
    </div>

    <form action="{{ route('admin.questions.update', $question) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="sg-card">
            <div class="sg-card-body">
                @include('admin.questions.partials.form')
            </div>
        </div>

        <div class="sg-mt-3 sg-text-center">
            <button class="sg-btn sg-btn-primary">
                <i class="fas fa-save"></i> Aggiorna
            </button>
        </div>
    </form>

    @auth @if(auth()->user()->canEditQuestion())
    <livewire:question-version-history :question-id="$question->id" :key="'qvh-'.$question->id" />
    @endif @endauth

</div>
@endsection
