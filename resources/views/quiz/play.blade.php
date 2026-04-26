@extends('layouts.admin')

@section('header', 'Quiz')

@section('content')

    <form id="quiz-form">

        @csrf

        @foreach($questions as $q)
            <div class="card mb-3">
                <div class="card-body">

                    <p><strong>{{ $q->question }}</strong></p>

                    @if($q->image)
                        <img src="{{ asset('storage/'.$q->image) }}" width="200" class="mb-2">
                    @endif

                    <div>
                        <label>
                            <input type="radio" name="answers[{{ $q->id }}]" value="1"> Vero
                        </label>

                        <label class="ml-3">
                            <input type="radio" name="answers[{{ $q->id }}]" value="0"> Falso
                        </label>
                    </div>

                </div>
            </div>
        @endforeach

        <button class="btn btn-success">Invia Quiz</button>

    </form>

@endsection

@section('js')
    <script>
        document.getElementById('quiz-form').addEventListener('submit', function(e) {
            e.preventDefault();

            let formData = new FormData(this);

            fetch("{{ route('quiz.submit') }}", {
                method: "POST",
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    alert("Punteggio: " + data.score);
                    window.location.href = "{{ route('quiz.results') }}";
                });
        });
    </script>
@endsection
