@extends('layouts.admin')

@section('header', 'Nuova Categoria')

@section('content')
    <form action="{{ route('categories.store') }}" method="POST">
        @csrf

        @include('admin.categories.partials.form')

        <button class="btn btn-success">Salva</button>
    </form>
@endsection
