@extends('layouts.admin')

@section('header', 'Risultati Quiz')

@section('content')

    <table class="table table-bordered">
        <thead>
        <tr>
            <th>Utente</th>
            <th>Punteggio</th>
            <th>Data</th>
        </tr>
        </thead>
        <tbody>
        @foreach($results as $r)
            <tr>
                <td>{{ $r->user->name }}</td>
                <td>{{ $r->score }}/{{ $r->total }}</td>
                <td>{{ $r->created_at }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

@endsection
