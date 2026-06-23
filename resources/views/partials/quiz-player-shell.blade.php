{{--
    Partial condiviso tra quiz.play e quiz-test.play.
    Variabili richieste: $quiz, $maxErrors
    Opzionali: $showReportButton (bool, default false), $firstQuestionId (int)
--}}
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

                    @if(!empty($showReportButton))
                        <div id="report-button-mount" class="mt-3">
                            <livewire:report-button :question-id="$firstQuestionId ?? 0" />
                        </div>
                    @endif

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
