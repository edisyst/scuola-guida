@extends('layouts.admin')

@section('content')

<form method="POST" action="{{ route('admin.users.store') }}">
    @csrf

    @include('admin.users.form')

    <button class="btn btn-success">Salva</button>
</form>

@endsection
