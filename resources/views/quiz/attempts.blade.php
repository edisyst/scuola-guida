@extends('layouts.admin')

@section('title', 'I miei tentativi')

@section('content_header')
    <h1>I miei tentativi</h1>
@endsection

@section('content')

    <div class="card">
        <div class="card-body p-0">

            @if($attempts->isEmpty())
                <div class="p-4 text-muted">
                    Nessun tentativo ancora. <a href="{{ route('quiz.play', 1) }}">Prova un quiz!</a>
                </div>
            @else
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Quiz</th>
                            <th>Punteggio</th>
                            <th>%</th>
                            <th>Esito</th>
                            <th>Durata</th>
                            <th>Data</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($attempts as $attempt)
                            <tr>
                                <td class="text-muted small align-middle">{{ $attempt->id }}</td>

                                <td class="align-middle">
                                    {{ $attempt->quiz->title ?? '—' }}
                                </td>

                                <td class="align-middle">
                                    <strong>{{ $attempt->score }}</strong> / {{ $attempt->total_questions }}
                                </td>

                                <td class="align-middle">
                                    {{ $attempt->percentage }}%
                                </td>

                                <td class="align-middle">
                                    @if($attempt->is_passed)
                                        <span class="badge badge-success">Superato</span>
                                    @else
                                        <span class="badge badge-danger">Non superato</span>
                                    @endif
                                </td>

                                <td class="align-middle text-muted small">
                                    @if($attempt->duration)
                                        {{ gmdate('i:s', $attempt->duration) }}
                                    @else
                                        —
                                    @endif
                                </td>

                                <td class="align-middle text-muted small">
                                    {{ $attempt->created_at->format('d/m/Y H:i') }}
                                </td>

                                <td class="align-middle">
                                    <a href="{{ route('quiz.attempts.show', $attempt) }}"
                                       class="btn btn-sm btn-outline-primary">
                                        Dettaglio
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="p-3">
                    {{ $attempts->links() }}
                </div>
            @endif

        </div>
    </div>

@endsection
