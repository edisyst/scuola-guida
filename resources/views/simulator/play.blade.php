@extends('layouts.admin')

@section('title', 'Simulatore Esame')

@section('content_header')@endsection

@section('css')
    @parent
@endsection

@section('content')
<div class="quiz-wrapper">

    <div class="quiz-header d-flex flex-wrap justify-content-between align-items-end">
        <div class="quiz-header-info">
            <p class="progress-label">{{ __('viewer.question_label') }} <span id="current-num">1</span> {{ __('viewer.of') }} <span id="total-num"></span></p>
            <h1 class="quiz-title">{{ __('viewer.simulator.title_full') }}</h1>
        </div>

        <div class="d-flex align-items-end gap-3">
            <div class="quiz-header-progress" style="min-width:140px">
                <p class="progress-label text-end mb-1"><span id="progress-percent">0%</span></p>
                <div class="quiz-progress">
                    <div id="progress-bar" class="bar" style="width:0%"></div>
                </div>
            </div>

            <form id="abandon-form" action="{{ route('simulator.destroy') }}" method="POST" class="m-0">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger"
                        onclick="return confirm('{{ __('viewer.simulator.abandon_confirm') }}');">
                    <i class="fas fa-times me-1"></i> {{ __('viewer.simulator.abandon') }}
                </button>
            </form>
        </div>
    </div>

    <div class="row">

        {{-- ── Colonna domanda ── --}}
        <div class="col-lg-8 mb-4">
            <div class="card question-card h-100">
                <div class="card-body p-4">

                    <span class="question-badge">{{ __('viewer.question_label') }} <span id="q-badge-num">1</span></span>

                    <div id="question-text"></div>

                    @auth @if(auth()->user()->tts_enabled)
                    <div class="mt-2"
                         x-data="{
                             text: @json($questionsJson[0]['text'] ?? ''),
                             speaking: false,
                             supported: 'speechSynthesis' in window,
                             autoplay: {{ json_encode((bool) auth()->user()->tts_autoplay) }},
                             speak() {
                                 if (!this.supported) return;
                                 window.speechSynthesis.cancel();
                                 const utt = new SpeechSynthesisUtterance(this.text);
                                 utt.lang = document.documentElement.lang || 'it-IT';
                                 utt.onend  = () => { this.speaking = false; };
                                 utt.onerror = () => { this.speaking = false; };
                                 this.speaking = true;
                                 window.speechSynthesis.speak(utt);
                             },
                             stop() {
                                 if (!this.supported) return;
                                 window.speechSynthesis.cancel();
                                 this.speaking = false;
                             },
                             init() {
                                 window.addEventListener('sim:question-loaded', (e) => {
                                     this.stop();
                                     this.text = e.detail.text;
                                     if (this.autoplay && this.supported) {
                                         this.$nextTick(() => this.speak());
                                     }
                                 });
                                 if (this.autoplay && this.supported) this.speak();
                             },
                         }">
                        <button type="button"
                                class="btn btn-sm btn-outline-secondary"
                                x-show="supported"
                                x-cloak
                                @click="speaking ? stop() : speak()"
                                :aria-pressed="speaking ? 'true' : 'false'"
                                aria-label="Leggi la domanda ad alta voce">
                            <i class="fas" :class="speaking ? 'fa-stop-circle' : 'fa-volume-up'"></i>
                            <span x-text="speaking ? 'Stop' : 'Ascolta'">Ascolta</span>
                        </button>
                    </div>
                    @endif @endauth

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

                    <div class="d-flex justify-content-between mt-4">
                        <button id="prev-question" class="btn btn-outline-secondary">
                            <i class="fas fa-chevron-left me-1"></i> {{ __('viewer.prev') }}
                        </button>
                        <button id="next-question" class="btn btn-outline-primary">
                            {{ __('viewer.next') }} <i class="fas fa-chevron-right ms-1"></i>
                        </button>
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
                    <button id="finish-quiz" class="btn btn-dark w-100">
                        <i class="fas fa-flag-checkered me-1"></i> {{ __('viewer.simulator.submit') }}
                    </button>
                </div>

            </div>
        </div>

    </div>

    {{-- ── Modal di conferma consegna ── --}}
    <div class="modal fade" id="confirm-submit-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-flag-checkered me-1"></i> {{ __('viewer.simulator.confirm_submit_title') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('viewer.simulator.back_to_sim') }}"></button>
                </div>
                <div class="modal-body">
                    <p>{{ __('viewer.simulator.confirm_submit_text') }}</p>
                    <ul class="list-unstyled mb-3">
                        <li><i class="fas fa-check-circle text-success me-1"></i>
                            {{ __('viewer.simulator.answers_given') }}: <strong id="summary-answered">0</strong> / <strong id="summary-total">0</strong></li>
                        <li><i class="fas fa-question-circle text-muted me-1"></i>
                            {{ __('viewer.simulator.unanswered') }}: <strong id="summary-skipped">0</strong></li>
                        <li><i class="fas fa-times-circle text-danger me-1"></i>
                            {{ __('viewer.simulator.errors_made') }}: <strong id="summary-errors">0</strong></li>
                    </ul>
                    <div class="alert alert-warning small mb-0">
                        <i class="fas fa-info-circle me-1"></i>
                        {{ __('viewer.simulator.unanswered_error_note') }}
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('viewer.simulator.back_to_sim') }}</button>
                    <button type="button" id="confirm-submit-btn" class="btn btn-success">
                        <i class="fas fa-paper-plane me-1"></i> {{ __('viewer.simulator.submit_anyway') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
    @parent
    @vite(['resources/js/tts.js'])
    @php
        $simUiStrings = [
            'correct_feedback'    => __('viewer.correct_feedback'),
            'wrong_feedback'      => __('viewer.wrong_feedback'),
            'error_limit_reached' => __('viewer.simulator.error_limit_reached'),
            'time_expired'        => __('viewer.simulator.time_expired'),
        ];
    @endphp

    <script>
        const questions  = @json($questionsJson);
        const answers    = {};
        const attemptId  = {{ $attempt->id }};
        const timeLimit  = {{ $timeLimit }};   // in secondi
        const maxErrors  = {{ $maxErrors }};
        const uiStrings  = @json($simUiStrings);

        let currentIndex     = 0;
        let errors           = 0;
        let autosaveTimeout  = null;
        let remainingSeconds = timeLimit;
        let simulatorFinished = false;

        window.addEventListener('beforeunload', () => autosave());

        function autosave() {
            clearTimeout(autosaveTimeout);
            autosaveTimeout = setTimeout(() => {
                $.ajax({
                    url: `/simulator/${attemptId}/autosave`,
                    method: 'PUT',
                    data: {
                        _token: "{{ csrf_token() }}",
                        answers,
                        duration: timeLimit - remainingSeconds
                    },
                    success: () => {},
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

            window.dispatchEvent(new CustomEvent('sim:question-loaded', { detail: { text: q.text } }));

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

            updateNavButtons();
        }

        function updateNavButtons() {
            $('#prev-question').prop('disabled', currentIndex === 0);
            $('#next-question').prop('disabled', currentIndex === questions.length - 1);
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
            if (simulatorFinished) return;

            const value = parseInt($(this).data('value'));
            const q     = questions[currentIndex];
            const isCorrect = value === q.correct;

            if (answers[q.id] !== undefined) {
                const wasCorrect = answers[q.id].correct === q.correct;
                if (!wasCorrect &&  isCorrect) errors--;
                if ( wasCorrect && !isCorrect) errors++;
            } else if (!isCorrect) {
                errors++;
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
                finishSimulator(uiStrings.error_limit_reached);
                return;
            }

            $('#feedback').html(
                isCorrect
                    ? `<span class="feedback-correct"><i class="fas fa-check-circle"></i> ${uiStrings.correct_feedback}</span>`
                    : `<span class="feedback-wrong"><i class="fas fa-times-circle"></i> ${uiStrings.wrong_feedback}</span>`
            );

            renderNavigator();
        });

        // ── Navigazione ──────────────────────────────────────
        $(document).on('click', '.nav-btn', function () {
            currentIndex = parseInt($(this).data('index'));
            renderQuestion(currentIndex);
            renderNavigator();
        });

        $('#prev-question').on('click', function () {
            if (currentIndex > 0) {
                currentIndex--;
                renderQuestion(currentIndex);
                renderNavigator();
            }
        });

        $('#next-question').on('click', function () {
            if (currentIndex < questions.length - 1) {
                currentIndex++;
                renderQuestion(currentIndex);
                renderNavigator();
            }
        });

        // ── Zoom immagine ────────────────────────────────────
        $(document).on('click', '#question-image img', function () {
            window.open($(this).attr('src'), '_blank');
        });

        // ── Termina (mostra modal di conferma) ───────────────
        $('#finish-quiz').on('click', function () {
            const answered = Object.keys(answers).length;
            const total    = questions.length;

            $('#summary-answered').text(answered);
            $('#summary-total').text(total);
            $('#summary-skipped').text(total - answered);
            $('#summary-errors').text(errors);

            const modal = new bootstrap.Modal(document.getElementById('confirm-submit-modal'));
            modal.show();
        });

        $('#confirm-submit-btn').on('click', function () {
            const modal = bootstrap.Modal.getInstance(document.getElementById('confirm-submit-modal'));
            if (modal) modal.hide();
            finishSimulator();
        });

        // ── Timer ────────────────────────────────────────────
        function startTimer() {
            updateTimerUI();
            const interval = setInterval(() => {
                if (simulatorFinished) { clearInterval(interval); return; }
                remainingSeconds--;
                updateTimerUI();
                if (remainingSeconds <= 0) {
                    clearInterval(interval);
                    finishSimulator(uiStrings.time_expired);
                }
            }, 1000);
        }

        function updateTimerUI() {
            const mm = String(Math.floor(remainingSeconds / 60)).padStart(2, '0');
            const ss = String(remainingSeconds % 60).padStart(2, '0');
            $('#timer').text(`${mm}:${ss}`);

            if (remainingSeconds < 60) {
                $('#timer').addClass('danger');
            }
        }

        // ── Finish: invia submit al SimulatorController ──────
        function finishSimulator(reason = '') {
            if (simulatorFinished) return;
            simulatorFinished = true;
            if ('speechSynthesis' in window) window.speechSynthesis.cancel();
            $('.btn-answer, #prev-question, #next-question, #finish-quiz').prop('disabled', true);

            // Disattiva l'autosave pendente per evitare race con il submit.
            clearTimeout(autosaveTimeout);

            const $form = $('<form>', { method: 'POST', action: '{{ route('simulator.submit') }}' });
            $form.append($('<input>', { type: 'hidden', name: '_token', value: '{{ csrf_token() }}' }));
            $form.append($('<input>', { type: 'hidden', name: 'duration', value: timeLimit - remainingSeconds }));

            Object.entries(answers).forEach(([qid, payload]) => {
                $form.append($('<input>', { type: 'hidden', name: `answers[${qid}][correct]`,            value: payload.correct }));
                $form.append($('<input>', { type: 'hidden', name: `answers[${qid}][answered_at]`,        value: payload.answered_at ?? '' }));
                $form.append($('<input>', { type: 'hidden', name: `answers[${qid}][time_spent_seconds]`, value: payload.time_spent_seconds ?? '' }));
                $form.append($('<input>', { type: 'hidden', name: `answers[${qid}][position]`,           value: payload.position ?? '' }));
            });

            if (reason && typeof toastr !== 'undefined') {
                toastr.warning(reason);
            }

            $('body').append($form);
            $form.trigger('submit');
        }

        // ── Init ─────────────────────────────────────────────
        $(document).ready(function () {
            $('#total-num').text(questions.length);
            renderQuestion(0);
            renderNavigator();
            renderErrorDots();
            startTimer();
        });
    </script>
@endsection
