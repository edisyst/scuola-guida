@extends('layouts.admin')

@section('title', $quiz->title ?? 'Prova Quiz')

@section('content_header')@endsection

@section('content')
<div class="quiz-wrapper">

    <div class="quiz-header d-flex flex-wrap justify-content-between align-items-end">
        <div class="quiz-header-info">
            <p class="progress-label">{{ __('viewer.question_label') }} <span id="current-num">1</span> {{ __('viewer.of') }} <span id="total-num"></span></p>
            <h1 class="quiz-title">{{ $quiz->title ?? 'Quiz' }}</h1>
        </div>
        <div class="quiz-header-progress" style="min-width:140px">
            <p class="progress-label text-end mb-1"><span id="progress-percent">0%</span></p>
            <div class="quiz-progress">
                <div id="progress-bar" class="bar" style="width:0%"></div>
            </div>
        </div>
    </div>

    <div class="row">

        {{-- Colonna domanda --}}
        <div class="col-lg-8 mb-4">
            <div class="card question-card h-100">
                <div class="card-body p-4">

                    <span class="question-badge">{{ __('viewer.question_label') }} <span id="q-badge-num">1</span></span>

                    <div id="question-text"></div>

                    <div id="question-image" class="mt-3"></div>

                    <div class="answer-area">
                        <button class="btn btn-answer" data-value="1">
                            <i class="fas fa-check"></i> {{ __('viewer.answer_true') }}
                        </button>
                        <button class="btn btn-answer" data-value="0">
                            <i class="fas fa-times"></i> {{ __('viewer.answer_false') }}
                        </button>
                    </div>

                    <div class="mt-3">
                        <span id="feedback"></span>
                    </div>

                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="col-lg-4 mb-4">
            <div class="card sidebar-card h-100 d-flex flex-column">

                <div class="sidebar-section text-center">
                    <p class="sidebar-label mb-1">{{ __('viewer.time_remaining') }}</p>
                    <span id="timer">00:00</span>
                </div>

                <div class="sidebar-section">
                    <p class="sidebar-label">{{ __('viewer.errors') }} <span id="errors-count">0</span> / {{ $maxErrors }}</p>
                    <div class="error-dots" id="error-dots"></div>
                </div>

                <div class="sidebar-section flex-grow-1">
                    <p class="sidebar-label">{{ __('viewer.quick_nav') }}</p>
                    <div id="navigator"></div>
                </div>

                <div class="sidebar-section">
                    <button id="finish-quiz" class="btn btn-dark">
                        <i class="fas fa-flag-checkered mr-1"></i> {{ __('viewer.quiz.end_quiz') }}
                    </button>
                </div>

            </div>
        </div>

    </div>
</div>

{{-- Modale risultati --}}
<div class="modal fade" id="resultModal" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header" id="result-header">
                <h5 class="modal-title" id="result-title"></h5>
            </div>
            <div class="modal-body text-center" id="result-body"></div>
            <div class="modal-footer">
                <a href="{{ route('quiz-test.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left mr-1"></i> Torna ai quiz
                </a>
                <button type="button" class="btn btn-primary" id="retry-btn">
                    <i class="fas fa-redo mr-1"></i> Riprova
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
    @parent
    @php
        $quizUiStrings = [
            'correct_feedback'    => __('viewer.correct_feedback'),
            'wrong_feedback'      => __('viewer.wrong_feedback'),
            'error_limit_reached' => __('viewer.error_limit_reached'),
            'time_expired'        => __('viewer.time_expired'),
            'answer_required'     => __('viewer.quiz.answer_required'),
        ];
    @endphp

    <script>
        const questions  = @json($questionsJson);
        const answers    = {};
        const timeLimit  = {{ $timeLimit }};
        const maxErrors  = {{ $maxErrors }};
        const uiStrings  = @json($quizUiStrings);
        const playUrl    = '{{ route('quiz-test.play', $quiz) }}';

        let currentIndex     = 0;
        let errors           = 0;
        let remainingSeconds = timeLimit;
        let quizFinished     = false;

        // ── Render domanda ──────────────────────────────────
        function renderQuestion(index) {
            updateProgress();

            const q   = questions[index];
            const num = index + 1;

            $('#question-text').text(q.text);
            $('#q-badge-num').text(num);
            $('#feedback').html('');

            if (q.image) {
                $('#question-image').html(
                    `<img src="${q.image}" class="img-fluid rounded shadow-sm sg-question-img">`
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
            const value    = parseInt($(this).data('value'));
            const q        = questions[currentIndex];
            const isCorrect = value === q.correct;

            if (answers[q.id] !== undefined) {
                const wasCorrect = answers[q.id].correct === q.correct;
                if (!wasCorrect &&  isCorrect) errors--;
                if ( wasCorrect && !isCorrect) errors++;
            } else {
                if (!isCorrect) errors++;
            }

            answers[q.id] = { correct: value, position: currentIndex + 1 };

            renderErrorDots();

            if (errors >= maxErrors) {
                finishQuiz(uiStrings.error_limit_reached);
                return;
            }

            $('#feedback').html(
                isCorrect
                    ? `<span class="feedback-correct"><i class="fas fa-check-circle"></i> ${uiStrings.correct_feedback}</span>`
                    : `<span class="feedback-wrong"><i class="fas fa-times-circle"></i> ${uiStrings.wrong_feedback}</span>`
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
                toastr.warning(uiStrings.answer_required);
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
                    finishQuiz(uiStrings.time_expired);
                }
            }, 1000);
        }

        function updateTimerUI() {
            const mm = String(Math.floor(remainingSeconds / 60)).padStart(2, '0');
            const ss = String(remainingSeconds % 60).padStart(2, '0');
            $('#timer').text(`${mm}:${ss}`);
            if (remainingSeconds < 300) $('#timer').addClass('danger');
        }

        // ── Finish — mostra risultati senza salvare ──────────
        function finishQuiz(reason = '') {
            if (quizFinished) return;
            quizFinished = true;
            $('.btn-answer').prop('disabled', true);

            const total    = questions.length;
            const answered = Object.keys(answers).length;
            const correct  = questions.filter(q =>
                answers[q.id] !== undefined && answers[q.id].correct === q.correct
            ).length;
            const passed   = errors < maxErrors;

            const header = $('#result-header');
            header.removeClass('bg-success bg-danger text-white');
            header.addClass(passed ? 'bg-success text-white' : 'bg-danger text-white');

            $('#result-title').text(passed ? 'PROMOSSO' : 'RIMANDATO');

            $('#result-body').html(`
                <div class="py-2">
                    <p class="mb-1"><strong>Domande:</strong> ${total}</p>
                    <p class="mb-1"><strong>Risposte date:</strong> ${answered}</p>
                    <p class="mb-1"><strong>Corrette:</strong> ${correct}</p>
                    <p class="mb-1"><strong>Errori:</strong> ${errors} / ${maxErrors}</p>
                    ${reason ? `<p class="mt-2 text-muted small">${reason}</p>` : ''}
                </div>
            `);

            $('#resultModal').modal('show');
        }

        // ── Riprova ──────────────────────────────────────────
        $('#retry-btn').click(function () {
            window.location.href = playUrl;
        });

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
