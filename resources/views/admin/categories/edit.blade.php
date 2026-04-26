@extends('layouts.admin')

@section('header', 'Modifica Categoria')

@section('content')
    <form action="{{ route('categories.update', $category) }}" method="POST">
        @csrf
        @method('PUT')

        @include('admin.categories.partials.form')

        <button class="btn btn-success">Aggiorna</button>
    </form>
@endsection
