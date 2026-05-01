@extends('layouts.admin')

@section('content')

    <div class="container">

        <h3 class="mb-4">
            {{ $quiz->title ?? 'Quiz Random' }}
        </h3>

        <div class="row">

            {{-- SINISTRA: DOMANDA --}}
            <div class="col-md-8">

                <div class="card p-4">

                    <div id="question-text" class="mb-4 h5"></div>

                    <div id="question-image" class="mb-4"></div>

                    <div>
                        <button class="btn btn-success btn-answer" data-value="1">
                            VERO
                        </button>

                        <button class="btn btn-danger btn-answer" data-value="0">
                            FALSO
                        </button>
                    </div>

                    <div class="mt-3">
                        <span id="feedback"></span>
                    </div>

                </div>

            </div>

            {{-- DESTRA: NAV + STATS --}}
            <div class="col-md-4">

                <div class="card p-3">

                    <h5>Errori: <span id="errors">0</span></h5>

                    <div id="navigator" class="mt-3"></div>

                </div>

            </div>

        </div>

    </div>

@endsection

@section('js')
    <script>
        const questions = @json($questionsJson);

        let currentIndex = 0;
        let errors = 0;

        const answers = {}; // {questionId: true/false}

        function renderQuestion(index) {
            const q = questions[index];

            $('#question-text').text((index+1) + '. ' + q.text);

            if (q.image) {
                $('#question-image').html(`<img src="${q.image}" width="200">`);
            } else {
                $('#question-image').html('');
            }

            $('#feedback').text('');
        }

        function renderNavigator() {
            let html = '';

            questions.forEach((q, i) => {

                let className = 'btn-outline-secondary';

                if (answers[q.id] !== undefined) {
                    className = answers[q.id] === q.correct
                        ? 'btn-success'
                        : 'btn-danger';
                }

                html += `
                <button class="btn btn-sm ${className} m-1 nav-btn" data-index="${i}">
                    ${i+1}
                </button>
            `;
            });

            $('#navigator').html(html);
        }

        // CLICK RISPOSTA
        $(document).on('click', '.btn-answer', function () {

            const value = parseInt($(this).data('value'));
            const q = questions[currentIndex];

            const isCorrect = value === q.correct;

            // se già risposto e cambio risposta
            if (answers[q.id] !== undefined) {
                if (answers[q.id] !== q.correct && isCorrect) {
                    errors--;
                }
                if (answers[q.id] === q.correct && !isCorrect) {
                    errors++;
                }
            } else {
                if (!isCorrect) errors++;
            }

            answers[q.id] = value;

            $('#errors').text(errors);

            $('#feedback').html(
                isCorrect
                    ? '<span class="text-success">✔ Corretta</span>'
                    : '<span class="text-danger">✖ Errata</span>'
            );

            renderNavigator();
        });

        // NAVIGAZIONE
        $(document).on('click', '.nav-btn', function () {
            currentIndex = $(this).data('index');
            renderQuestion(currentIndex);
        });

        // INIT
        $(document).ready(function () {
            renderQuestion(0);
            renderNavigator();
        });

    </script>
@endsection
