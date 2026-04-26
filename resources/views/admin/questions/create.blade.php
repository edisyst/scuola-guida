@extends('layouts.admin')

@section('header', 'Nuova Domanda')

@section('content')
    <form action="{{ route('questions.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        @include('admin.questions.partials.form')

        <button class="btn btn-success">Salva</button>
    </form>
@endsection
