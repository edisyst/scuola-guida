@extends('layouts.admin')

@section('content')

    <form method="POST" action="{{ route('admin.quizzes.update', $quiz) }}">
        @csrf
        @method('PUT')

        @include('admin.quizzes.partials.form')

        <button class="btn btn-success">Aggiorna</button>
    </form>

@endsection
