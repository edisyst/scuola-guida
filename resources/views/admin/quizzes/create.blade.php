@extends('layouts.admin')

@section('content')

    <form method="POST" action="{{ route('admin.quizzes.store') }}">
        @csrf

        @include('admin.quizzes.partials.form')

        <button class="btn btn-success">Salva</button>
    </form>

@endsection
