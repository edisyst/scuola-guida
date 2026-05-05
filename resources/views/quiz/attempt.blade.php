@extends('layouts.admin')

@section('content')

    <div class="container">

        <h3>Risultato Quiz</h3>

        <div class="card p-4">

            <h4>
                {{ $attempt->score }} / {{ $attempt->total_questions }}
            </h4>

            <p>
                Percentuale: {{ $attempt->percentage }}%
            </p>

            <p>
                Esito:
                @if($attempt->is_passed)
                    <span class="text-success">SUPERATO</span>
                @else
                    <span class="text-danger">NON SUPERATO</span>
                @endif
            </p>

            <p>
                Tempo: {{ $attempt->duration ?? '-' }} sec
            </p>

        </div>

    </div>

@endsection
