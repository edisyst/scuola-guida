@extends('layouts.admin')

@section('content')

    <div class="container">

        <h3 class="mb-4">
            {{ $quiz->title ?? 'Quiz Random' }}
        </h3>

    <div class="d-flex justify-content-between mb-3">
        <strong>
            Domanda <span id="current-num">1</span> / <span id="total-num"></span>
        </strong>
        <span id="progress-percent"></span>
    </div>

    <div class="progress mb-3">
        <div id="progress-bar" class="progress-bar" style="width:0%"></div>
    </div>

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

                    <button id="finish-quiz" class="btn btn-primary mt-3">
                        Termina Quiz
                    </button>

                </div>

            </div>

            {{-- DESTRA: NAV + STATS --}}
            <div class="col-md-4">

                <div class="card p-3">
                    <div class="mb-3">
                        <h5>
                            Tempo Rimasto:
                            <span id="timer" class="text-primary"></span>
                        </h5>
                    </div>

                    <h5>Errori: <span id="errors">0</span></h5>

                    <div id="navigator" class="mt-3"></div>

                </div>

            </div>

        </div>

    </div>

@endsection

@section('js')
    @parent

    <script>
        const questions = @json($questionsJson);
        const answers = {}; // {questionId: true/false}
        const attemptId = {{ $attemptId }};
        const timeLimit = {{ $timeLimit }};
        const maxErrors = {{ $maxErrors }};

        let currentIndex = 0;
        let errors = 0;
        let autosaveTimeout = null;
        let remainingSeconds = timeLimit;
        let quizFinished = false;

        window.addEventListener('beforeunload', function () {
            autosave();
        });

        function autosave() {
            // debounce per evitare spam richieste
            clearTimeout(autosaveTimeout);

            autosaveTimeout = setTimeout(() => {

                $.ajax({
                    url: `/quiz/attempts/${attemptId}`,
                    method: 'PUT',
                    data: {
                        _token: "{{ csrf_token() }}",
                        answers: answers,
                        duration: timeLimit - remainingSeconds
                    },
                    success: function () {
                        console.log('autosave ok');
                    },
                    error: function () {
                        console.error('autosave error');
                    }
                });
            }, 300); // 🔥 debounce 300ms
        }

        // RENDER DOMANDA
        function renderQuestion(index) {
            updateProgress();

            const q = questions[index];
            $('#question-text').text((index+1) + '. ' + q.text);

            if (q.image) {
                $('#question-image').html(`
                    <img src="${q.image}" class="img-fluid rounded shadow-sm" style="max-height:200px; cursor:pointer;">
                `);
            } else {
                $('#question-image').html('');
            }

            $('#feedback').text('');
        }

        // RENDER NAVIGAZIONE
        function renderNavigator() {
            let html = '';
            questions.forEach((q, i) => {

                let className = 'btn-outline-secondary';

                // 👉 stato risposta (PRIORITARIO)
                if (answers[q.id] !== undefined) {
                    className = answers[q.id] === q.correct
                        ? 'btn-success'
                        : 'btn-danger';
                }

                // 👉 stato corrente (NON sovrascrive colore)
                let activeClass = (i === currentIndex) ? 'border border-dark' : '';

                html += `
                    <button class="btn btn-sm ${className} ${activeClass} m-1 nav-btn"
                        data-index="${i}">
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

            answers[q.id] = value; // 🔥 salva 0/1 invece di true/false
            autosave(); // 🔥 salvataggio automatico

            $('#errors').text(errors);

            if (errors >= maxErrors) {
                finishQuiz('Limite errori raggiunto');
                return;
            }

            $('#feedback').html(
                isCorrect
                    ? '<span class="text-success">✔ Corretta</span>'
                    : '<span class="text-danger">✖ Errata</span>'
            );

            renderNavigator();

            setTimeout(() => {
                if (currentIndex < questions.length - 1) {
                    currentIndex++;
                    renderQuestion(currentIndex);
                }
            }, 400);
        });

        //UPDATE BARRA
        function updateProgress() {
            let current = currentIndex + 1;
            let total = questions.length;
            let percent = Math.round((current / total) * 100);

            $('#current-num').text(current);
            $('#total-num').text(total);
            $('#progress-percent').text(percent + '%');

            $('#progress-bar').css('width', percent + '%');
        }

        // NAVIGAZIONE
        $(document).on('click', '.nav-btn', function () {
            currentIndex = $(this).data('index');
            renderQuestion(currentIndex);
        });

        // ZOOM
        $(document).on('click', '#question-image img', function () {
            window.open($(this).attr('src'), '_blank');
        });

        $('#finish-quiz').click(function () {

            if (Object.keys(answers).length === 0) {
                toastr.warning('Nessuna risposta');
                return;
            }

            finishQuiz();

            $.post("{{ route('quiz.attempts.store') }}", {
                _token: "{{ csrf_token() }}",
                quiz_id: {{ $quiz->id }},
                answers: answers,
                duration: 0 // 🔥 poi colleghiamo il timer
            }, function (res) {

                toastr.success(`Risultato: ${res.score}/${res.total}`);

                window.location.href = `/quiz/attempts/${res.attempt_id}`;

            }).fail(function (xhr) {

                toastr.error('Errore salvataggio');

                console.error(xhr.responseJSON);

            });
        });

        function startTimer() {
            updateTimerUI();

            const interval = setInterval(function () {
                if (quizFinished) {
                    clearInterval(interval);
                    return;
                }

                remainingSeconds--;

                updateTimerUI();
                // autosave tempo
                autosave();

                // tempo finito
                if (remainingSeconds <= 0) {
                    clearInterval(interval);
                    finishQuiz('Tempo scaduto');
                }
            }, 1000);
        }

    function updateTimerUI() {
        let minutes = Math.floor(remainingSeconds / 60);
        let seconds = remainingSeconds % 60;

        minutes = String(minutes).padStart(2, '0');
        seconds = String(seconds).padStart(2, '0');

        $('#timer').text(`${minutes}:${seconds}`);

        // warning colori
        if (remainingSeconds < 300) {
            $('#timer').removeClass('text-primary')
                .addClass('text-danger');
        }
    }

    function finishQuiz(reason = '') {
        if (quizFinished) return;

        quizFinished = true;
        $('.btn-answer').prop('disabled', true);

        $.post("{{ route('quiz.attempts.store') }}", {
            _token: "{{ csrf_token() }}",
            quiz_id: {{ $quiz->id }},
            answers: answers,
            duration: timeLimit - remainingSeconds

        }, function (res) {
            if (reason) {
                toastr.warning(reason);
            }
            window.location.href = `/quiz/attempts/${res.attempt_id}`;

        }).fail(function () {
            toastr.error('Errore submit finale');
        });
    }

        // INIT
        $(document).ready(function () {
            renderQuestion(0);
            renderNavigator();
            startTimer();

            $('.btn-answer').removeClass('active');

            $(this).addClass('active');

            // disabilita temporaneamente
            $('.btn-answer').prop('disabled', true);

            setTimeout(() => {
                $('.btn-answer').prop('disabled', false);
            }, 400);
        });

    </script>
@endsection
