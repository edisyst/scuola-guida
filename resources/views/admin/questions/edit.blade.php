@extends('layouts.admin')

@section('header', 'Modifica Domanda')

@section('content')
    <form action="{{ route('questions.update', $question) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        @include('admin.questions.partials.form')

        <button class="btn btn-success">Aggiorna</button>
    </form>
@endsection
