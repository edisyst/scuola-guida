@extends('layouts.admin')

@section('title', 'Risultati Quiz')

@section('content_header')@endsection

@section('content')
<div class="sg-wrapper">

    <div class="sg-header">
        <p class="sg-header-subtitle">Storico globale</p>
        <h1 class="sg-header-title">Risultati Quiz</h1>
    </div>

    <div class="sg-card">
        <div class="table-responsive">
            <table class="sg-table">
                <thead>
                    <tr>
                        <th>Utente</th>
                        <th>Punteggio</th>
                        <th>Data</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($results as $r)
                        <tr>
                            <td>{{ $r->user->name }}</td>
                            <td><strong>{{ $r->score }}</strong> <span class="sg-text-muted">/ {{ $r->total }}</span></td>
                            <td class="sg-text-muted">{{ $r->created_at }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="sg-table-empty">Nessun risultato disponibile.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
