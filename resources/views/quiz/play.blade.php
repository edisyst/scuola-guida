@extends('layouts.admin')

@section('title', $quiz->title ?? 'Quiz')

@section('content_header')@endsection

@section('css')
    @parent
    <style>
        /* ── layout ── */
        .quiz-wrapper {
            max-width: 1100px;
            margin: 0 auto;
        }

        /* ── header barra ── */
        .quiz-header {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            border-radius: 12px;
            padding: 18px 24px;
            margin-bottom: 20px;
            color: #fff;
        }
        .quiz-title {
            font-size: 1.15rem;
            font-weight: 600;
            letter-spacing: .3px;
            margin: 0;
        }
        .progress-label {
            font-size: .8rem;
            color: rgba(255,255,255,.65);
            margin-bottom: 4px;
        }
        .quiz-progress {
            height: 8px;
            border-radius: 4px;
            background: rgba(255,255,255,.15);
            overflow: hidden;
        }
        .quiz-progress .bar {
            height: 100%;
            border-radius: 4px;
            background: linear-gradient(90deg, #28a745, #20c997);
            transition: width .4s ease;
        }

        /* ── card domanda ── */
        .question-card {
            border: none;
            border-radius: 14px;
            box-shadow: 0 4px 20px rgba(0,0,0,.08);
        }
        .question-badge {
            display: inline-block;
            background: #e9ecef;
            color: #495057;
            font-size: .75rem;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 20px;
            margin-bottom: 16px;
            letter-spacing: .5px;
            text-transform: uppercase;
        }
        #question-text {
            font-size: 1.25rem;
            font-weight: 500;
            line-height: 1.6;
            color: #212529;
            min-height: 80px;
        }

        /* ── bottoni risposta ── */
        .answer-area {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            margin-top: 28px;
        }
        .btn-answer {
            padding: 18px 12px;
            font-size: 1.05rem;
            font-weight: 700;
            letter-spacing: 1.5px;
            border-radius: 10px;
            border: 2px solid transparent;
            transition: transform .12s ease, box-shadow .12s ease, opacity .15s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-answer[data-value="1"] {
            background: #28a745;
            color: #fff;
            border-color: #28a745;
        }
        .btn-answer[data-value="0"] {
            background: #dc3545;
            color: #fff;
            border-color: #dc3545;
        }
        .btn-answer:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0,0,0,.18);
        }
        .btn-answer:active:not(:disabled) {
            transform: translateY(0);
        }
        .btn-answer:disabled {
            opacity: .55;
            cursor: not-allowed;
        }

        /* ── feedback ── */
        #feedback {
            font-size: 1rem;
            font-weight: 600;
            min-height: 28px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .feedback-correct  { color: #28a745; }
        .feedback-wrong    { color: #dc3545; }

        /* ── sidebar card ── */
        .sidebar-card {
            border: none;
            border-radius: 14px;
            box-shadow: 0 4px 20px rgba(0,0,0,.08);
            overflow: hidden;
        }
        .sidebar-section {
            padding: 14px 18px;
            border-bottom: 1px solid #f1f3f5;
        }
        .sidebar-section:last-child { border-bottom: none; }
        .sidebar-label {
            font-size: .7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .8px;
            color: #adb5bd;
            margin-bottom: 6px;
        }

        /* ── timer ── */
        #timer {
            font-size: 2.2rem;
            font-weight: 800;
            font-variant-numeric: tabular-nums;
            letter-spacing: 2px;
            line-height: 1;
            color: #343a40;
        }
        #timer.danger { color: #dc3545; }

        /* ── errori ── */
        .error-dots {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            margin-top: 4px;
        }
        .error-dot {
            width: 14px;
            height: 14px;
            border-radius: 50%;
            border: 2px solid #dee2e6;
            background: transparent;
            transition: background .2s, border-color .2s;
        }
        .error-dot.filled {
            background: #dc3545;
            border-color: #dc3545;
        }

        /* ── navigatore ── */
        #navigator {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }
        .nav-btn {
            width: 32px;
            height: 32px;
            padding: 0;
            font-size: .75rem;
            font-weight: 600;
            border-radius: 6px;
            border: 2px solid #dee2e6;
            background: #fff;
            color: #495057;
            transition: transform .1s;
        }
        .nav-btn:hover { transform: scale(1.1); }
        .nav-btn.answered-ok  { background: #28a745; border-color: #28a745; color: #fff; }
        .nav-btn.answered-ko  { background: #dc3545; border-color: #dc3545; color: #fff; }
        .nav-btn.current      { border-color: #343a40 !important; box-shadow: 0 0 0 2px rgba(52,58,64,.25); }

        /* ── bottone termina ── */
        #finish-quiz {
            width: 100%;
            padding: 12px;
            font-weight: 700;
            letter-spacing: .5px;
            border-radius: 10px;
            font-size: .95rem;
        }
    </style>
@endsection

@section('content')
<div class="quiz-wrapper">

    {{-- ── Header ── --}}
    <div class="quiz-header d-flex justify-content-between align-items-end">
        <div>
            <p class="progress-label">Domanda <span id="current-num">1</span> di <span id="total-num"></span></p>
            <h1 class="quiz-title">{{ $quiz->title ?? 'Quiz Random' }}</h1>
        </div>
        <div style="min-width:140px">
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
                    cls = answers[q.id] === q.correct ? 'answered-ok' : 'answered-ko';
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
                const wasCorrect = answers[q.id] === q.correct;
                if (!wasCorrect &&  isCorrect) errors--;
                if ( wasCorrect && !isCorrect) errors++;
            } else {
                if (!isCorrect) errors++;
            }

            answers[q.id] = value;
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
