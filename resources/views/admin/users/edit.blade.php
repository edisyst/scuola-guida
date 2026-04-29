@extends('layouts.admin')

@section('content')

<form method="POST" action="{{ route('admin.users.update', $user) }}">
    @csrf
    @method('PUT')

    @include('admin.users.form')

    <button class="btn btn-success">Aggiorna</button>
</form>

@endsection
