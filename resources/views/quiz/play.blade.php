@extends('layouts.admin')

@section('title', $quiz->title ?? 'Quiz')

@section('content_header')@endsection

@section('css')
    @parent
@endsection

@section('content')
@include('partials.quiz-player-shell', [
    'showReportButton' => auth()->check() && auth()->user()->isViewer(),
    'firstQuestionId'  => $questionsJson[0]['id'] ?? 0,
])
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
            'save_error'          => __('viewer.quiz.save_error'),
        ];
    @endphp

    <script>
        const questions  = @json($questionsJson);
        const answers    = {};
        const attemptId  = {{ $attemptId }};
        const timeLimit  = {{ $timeLimit }};
        const maxErrors  = {{ $maxErrors }};
        const uiStrings  = @json($quizUiStrings);

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
                    `<img src="${q.image}" class="img-fluid rounded shadow-sm sg-question-img">`
                );
            } else {
                $('#question-image').html('');
            }
        }

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

        function renderErrorDots() {
            let html = '';
            for (let i = 0; i < maxErrors; i++) {
                html += `<span class="error-dot ${i < errors ? 'filled' : ''}"></span>`;
            }
            $('#error-dots').html(html);
            $('#errors-count').text(errors);
        }

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

        $(document).on('click', '.nav-btn', function () {
            currentIndex = parseInt($(this).data('index'));
            renderQuestion(currentIndex);
            renderNavigator();
        });

        $(document).on('click', '#question-image img', function () {
            window.open($(this).attr('src'), '_blank');
        });

        $('#finish-quiz').click(function () {
            if (Object.keys(answers).length === 0) {
                toastr.warning(uiStrings.answer_required);
                return;
            }
            finishQuiz();
        });

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

            if (remainingSeconds < 300) {
                $('#timer').addClass('danger');
            }
        }

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
                error: () => toastr.error(uiStrings.save_error)
            });
        }

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
