@extends('layouts.admin')

@section('title', $quiz->title ?? 'Quiz')

@section('content_header')@endsection

@section('css')
    @parent
@endsection

@section('content')
<div class="quiz-wrapper">

    {{-- ── Header ──
         Mobile: titolo e progress vanno in colonna grazie a .quiz-header CSS
         (vedi media query in scuola-guida.css). --}}
    <div class="quiz-header d-flex flex-wrap justify-content-between align-items-end">
        <div class="quiz-header-info">
            <p class="progress-label">Domanda <span id="current-num">1</span> di <span id="total-num"></span></p>
            <h1 class="quiz-title">{{ $quiz->title ?? 'Quiz Random' }}</h1>
        </div>
        <div class="quiz-header-progress" style="min-width:140px">
            <p class="progress-label text-end mb-1"><span id="progress-percent">0%</span></p>
            <div class="quiz-progress">
                <div id="progress-bar" class="bar" style="width:0%"></div>
            </div>
        </div>
    </div>

    <div class="row">

        {{-- ── Colonna domanda ── --}}
        <div class="col-lg-8 mb-4">
            <div class="card question-card h-100">
                <div class="card-body p-4">

                    <span class="question-badge">Domanda <span id="q-badge-num">1</span></span>

                    <div id="question-text"></div>

                    <div id="question-image" class="mt-3"></div>

                    <div class="answer-area">
                        <button class="btn btn-answer" data-value="1">
                            <i class="fas fa-check"></i> VERO
                        </button>
                        <button class="btn btn-answer" data-value="0">
                            <i class="fas fa-times"></i> FALSO
                        </button>
                    </div>

                    <div class="mt-3">
                        <span id="feedback"></span>
                    </div>

                    @auth @if(auth()->user()->isViewer())
                        <div id="report-button-mount" class="mt-3">
                            <livewire:report-button :question-id="$questionsJson[0]['id'] ?? 0" />
                        </div>
                    @endif @endauth

                </div>
            </div>
        </div>

        {{-- ── Sidebar ── --}}
        <div class="col-lg-4 mb-4">
            <div class="card sidebar-card h-100 d-flex flex-column">

                {{-- Timer --}}
                <div class="sidebar-section text-center">
                    <p class="sidebar-label mb-1">Tempo rimasto</p>
                    <span id="timer">00:00</span>
                </div>

                {{-- Errori --}}
                <div class="sidebar-section">
                    <p class="sidebar-label">Errori <span id="errors-count">0</span> / {{ $maxErrors }}</p>
                    <div class="error-dots" id="error-dots"></div>
                </div>

                {{-- Navigatore --}}
                <div class="sidebar-section flex-grow-1">
                    <p class="sidebar-label">Navigazione rapida</p>
                    <div id="navigator"></div>
                </div>

                {{-- Termina --}}
                <div class="sidebar-section">
                    <button id="finish-quiz" class="btn btn-dark">
                        <i class="fas fa-flag-checkered mr-1"></i> Termina Quiz
                    </button>
                </div>

            </div>
        </div>

    </div>
</div>
@endsection

@section('js')
    @parent

    <script>
        const questions  = @json($questionsJson);
        const answers    = {};
        const attemptId  = {{ $attemptId }};
        const timeLimit  = {{ $timeLimit }};
        const maxErrors  = {{ $maxErrors }};

        let currentIndex    = 0;
        let errors          = 0;
        let autosaveTimeout = null;
        let remainingSeconds = timeLimit;
        let quizFinished    = false;

        window.addEventListener('beforeunload', () => autosave());

        function autosave() {
            clearTimeout(autosaveTimeout);
            autosaveTimeout = setTimeout(() => {
                $.ajax({
                    url: `/quiz/attempts/${attemptId}`,
                    method: 'PUT',
                    data: {
                        _token: "{{ csrf_token() }}",
                        answers,
                        duration: timeLimit - remainingSeconds
                    },
                    success: () => console.log('autosave ok'),
                    error:   () => console.error('autosave error')
                });
            }, 1000);
        }

        // ── Render domanda ──────────────────────────────────
        function renderQuestion(index) {
            updateProgress();

            const q = questions[index];
            const num = index + 1;

            $('#question-text').text(q.text);
            $('#q-badge-num').text(num);
            $('#feedback').html('');

            if (window.Livewire) {
                window.Livewire.dispatch('report-button-set-question', { id: q.id });
            }

            if (q.image) {
                $('#question-image').html(
                    `<img src="${q.image}" class="img-fluid rounded shadow-sm"
                          style="max-height:220px; cursor:pointer;">`
                );
            } else {
                $('#question-image').html('');
            }
        }

        // ── Navigatore ──────────────────────────────────────
        function renderNavigator() {
            let html = '';
            questions.forEach((q, i) => {
                let cls = '';
                if (answers[q.id] !== undefined) {
                    cls = answers[q.id].correct === q.correct ? 'answered-ok' : 'answered-ko';
                }
                if (i === currentIndex) cls += ' current';

                html += `<button class="nav-btn ${cls}" data-index="${i}">${i + 1}</button>`;
            });
            $('#navigator').html(html);
        }

        // ── Punti errore ─────────────────────────────────────
        function renderErrorDots() {
            let html = '';
            for (let i = 0; i < maxErrors; i++) {
                html += `<span class="error-dot ${i < errors ? 'filled' : ''}"></span>`;
            }
            $('#error-dots').html(html);
            $('#errors-count').text(errors);
        }

        // ── Progresso ────────────────────────────────────────
        function updateProgress() {
            const current = currentIndex + 1;
            const total   = questions.length;
            const pct     = Math.round((current / total) * 100);

            $('#current-num').text(current);
            $('#q-badge-num').text(current);
            $('#total-num').text(total);
            $('#progress-percent').text(pct + '%');
            $('#progress-bar').css('width', pct + '%');
        }

        // ── Click risposta ───────────────────────────────────
        $(document).on('click', '.btn-answer', function () {
            const value = parseInt($(this).data('value'));
            const q     = questions[currentIndex];
            const isCorrect = value === q.correct;

            if (answers[q.id] !== undefined) {
                const wasCorrect = answers[q.id].correct === q.correct;
                if (!wasCorrect &&  isCorrect) errors--;
                if ( wasCorrect && !isCorrect) errors++;
            } else {
                if (!isCorrect) errors++;
            }

            answers[q.id] = {
                correct:            value,
                answered_at:        Math.floor(Date.now() / 1000),
                time_spent_seconds: null,
                position:           currentIndex + 1,
            };
            autosave();

            renderErrorDots();

            if (errors >= maxErrors) {
                finishQuiz('Hai raggiunto il limite di errori');
                return;
            }

            $('#feedback').html(
                isCorrect
                    ? '<span class="feedback-correct"><i class="fas fa-check-circle"></i> Risposta corretta</span>'
                    : '<span class="feedback-wrong"><i class="fas fa-times-circle"></i> Risposta errata</span>'
            );

            renderNavigator();

            setTimeout(() => {
                if (currentIndex < questions.length - 1) {
                    currentIndex++;
                    renderQuestion(currentIndex);
                    renderNavigator();
                }
            }, 500);
        });

        // ── Navigazione rapida ───────────────────────────────
        $(document).on('click', '.nav-btn', function () {
            currentIndex = parseInt($(this).data('index'));
            renderQuestion(currentIndex);
            renderNavigator();
        });

        // ── Zoom immagine ────────────────────────────────────
        $(document).on('click', '#question-image img', function () {
            window.open($(this).attr('src'), '_blank');
        });

        // ── Termina ──────────────────────────────────────────
        $('#finish-quiz').click(function () {
            if (Object.keys(answers).length === 0) {
                toastr.warning('Rispondi almeno a una domanda prima di terminare');
                return;
            }
            finishQuiz();
        });

        // ── Timer ────────────────────────────────────────────
        function startTimer() {
            updateTimerUI();
            const interval = setInterval(() => {
                if (quizFinished) { clearInterval(interval); return; }
                remainingSeconds--;
                updateTimerUI();
                if (remainingSeconds <= 0) {
                    clearInterval(interval);
                    finishQuiz('Tempo scaduto!');
                }
            }, 1000);
        }

        function updateTimerUI() {
            const mm = String(Math.floor(remainingSeconds / 60)).padStart(2, '0');
            const ss = String(remainingSeconds % 60).padStart(2, '0');
            $('#timer').text(`${mm}:${ss}`);

            if (remainingSeconds < 300) {
                $('#timer').addClass('danger');
            }
        }

        // ── Finish ───────────────────────────────────────────
        function finishQuiz(reason = '') {
            if (quizFinished) return;
            quizFinished = true;
            $('.btn-answer').prop('disabled', true);

            $.ajax({
                url: `/quiz/attempts/${attemptId}`,
                method: 'PUT',
                data: {
                    _token: "{{ csrf_token() }}",
                    answers,
                    duration: timeLimit - remainingSeconds
                },
                success: () => {
                    if (reason) toastr.warning(reason);
                    window.location.href = `/quiz/attempts/${attemptId}`;
                },
                error: () => toastr.error('Errore nel salvataggio del quiz')
            });
        }

        // ── Init ─────────────────────────────────────────────
        $(document).ready(function () {
            renderQuestion(0);
            renderNavigator();
            renderErrorDots();
            startTimer();

            $('.btn-answer').prop('disabled', true);
            setTimeout(() => $('.btn-answer').prop('disabled', false), 400);
        });
    </script>
@endsection
