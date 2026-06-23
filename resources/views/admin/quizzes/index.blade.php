@extends('layouts.admin')

@section('title', __('quiz.title'))
@section('content_header')@endsection

@section('content')
<div class="sg-wrapper-fluid">

    <div class="sg-header sg-flex-between">
        <div>
            <p class="sg-header-subtitle">{{ __('quiz.subtitle') }}</p>
            <h1 class="sg-header-title"><i class="fas fa-clipboard-check mr-2"></i> {{ __('quiz.title') }}</h1>
        </div>
        @if(auth()->user()->canCreateQuiz())
            <div class="sg-header-actions">
                <a href="{{ route('admin.quizzes.create') }}" class="sg-btn sg-btn-light sg-btn-sm">
                    <i class="fas fa-plus"></i> {{ __('quiz.action_new') }}
                </a>
                <form method="POST" action="{{ route('admin.quizzes.random') }}" class="d-inline">
                    @csrf
                    <button class="sg-btn sg-btn-success sg-btn-sm">
                        <i class="fas fa-random"></i> {{ __('quiz.action_random') }}
                    </button>
                </form>
            </div>
        @endif
    </div>

    <div class="sg-card sg-card-body-tight mb-3">
        <p class="sg-text-muted mb-2"><i class="fas fa-info-circle mr-1"></i> {{ __('quiz.states_legend') }}</p>
        <ul class="list-unstyled mb-0 small">
            <li class="mb-1">
                <span class="sg-badge">{{ __('quiz.status_draft') }}</span>
                — {{ __('quiz.status_draft_desc') }}
            </li>
            <li class="mb-1">
                <span class="sg-badge sg-badge-success">{{ __('quiz.status_published') }}</span>
                — {{ __('quiz.status_published_desc') }}
            </li>
            <li>
                <span class="sg-badge sg-badge-info"><i class="fas fa-lock"></i> {{ __('quiz.status_confirmed') }}</span>
                — {{ __('quiz.status_confirmed_desc') }}
            </li>
        </ul>
    </div>

    <div class="sg-card">
        <div class="sg-card-body" style="padding:1.25rem;">
            <form method="GET" action="{{ route('admin.quizzes.index') }}" class="row sg-mb-2 align-items-end">
                <div class="col-12 col-md-4 sg-mb-1">
                    <label class="sg-label mb-2">{{ __('quiz.filter_license_type') }}</label>
                    <select name="license_type_id" class="sg-form-control">
                        <option value="">{{ __('quiz.filter_license_type_all') }}</option>
                        @foreach($licenseTypes as $lt)
                            <option value="{{ $lt->id }}" @selected($licenseTypeId == $lt->id)>{{ $lt->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-2 sg-mb-1">
                    <button type="submit" class="sg-btn sg-btn-primary sg-btn-sm" style="width:100%;">
                        <i class="fas fa-filter"></i> {{ __('common.filter') }}
                    </button>
                </div>
                <div class="col-12 col-md-2 sg-mb-1">
                    <a href="{{ route('admin.quizzes.index') }}" class="sg-btn sg-btn-secondary sg-btn-sm" style="width:100%;">
                        <i class="fas fa-times"></i> {{ __('common.reset') }}
                    </a>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="sg-table" id="quiz-table">
                <thead>
                    <tr>
                        <th>{{ __('quiz.col_id') }}</th>
                        <th>{{ __('quiz.col_title') }}</th>
                        <th>{{ __('quiz.col_status') }}</th>
                        <th>{{ __('quiz.col_questions') }}</th>
                        <th>{{ __('quiz.col_license_type') }}</th>
                        <th class="text-right" style="width:360px;">{{ __('quiz.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($quizzes as $quiz)
                        <tr>
                            <td class="sg-text-muted">{{ $quiz->id }}</td>
                            <td><strong>{{ $quiz->title }}</strong></td>
                            <td>
                                @if($quiz->isConfirmed())
                                    <span class="sg-badge sg-badge-info"><i class="fas fa-lock"></i> {{ __('quiz.status_confirmed') }}</span>
                                @elseif($quiz->isPublished())
                                    <span class="sg-badge sg-badge-success">{{ __('quiz.status_published') }}</span>
                                @else
                                    <span class="sg-badge">{{ __('quiz.status_draft') }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="sg-badge sg-badge-info">{{ $quiz->questions_count ?? 0 }}/{{ $quiz->max_questions ?? 0 }}</span>
                            </td>
                            <td>
                                @if($quiz->licenseType)
                                    <span class="sg-badge">{{ $quiz->licenseType->code }}</span>
                                @else
                                    <span class="sg-text-muted">—</span>
                                @endif
                            </td>
                            <td class="sg-actions-cell">
                                @php
                                    $hasQuestions = ($quiz->questions_count ?? 0) > 0;
                                    $canPlayHere = $quiz->isPublished()
                                        || ($quiz->isDraft() && (auth()->user()->canEditQuiz() || auth()->user()->isAdmin()));
                                @endphp
                                @if($hasQuestions && $canPlayHere)
                                    <a href="{{ route('quiz.play', $quiz) }}" class="sg-btn-icon info" title="Play">
                                        <i class="fas fa-play"></i>
                                    </a>
                                @elseif($canPlayHere)
                                    <span class="sg-btn-icon info sg-btn-icon--disabled" title="{{ __('quiz.tooltip_no_questions') }}">
                                        <i class="fas fa-play"></i>
                                    </span>
                                @endif

                                @if(auth()->user()->canEditQuiz() && !$quiz->isLocked())
                                    <a href="{{ route('admin.quizzes.questions', $quiz) }}" class="sg-btn-icon info" title="{{ __('quiz.action_questions') }}">
                                        <i class="fas fa-tasks"></i>
                                    </a>
                                @else
                                    <span class="sg-btn-icon info sg-btn-icon--disabled" title="{{ __('quiz.tooltip_questions_locked') }}">
                                        <i class="fas fa-tasks"></i>
                                    </span>
                                @endif

                                @if(auth()->user()->canBulkQuiz() && !$quiz->isLocked())
                                    <form method="POST" action="{{ route('admin.quizzes.fillRandom', $quiz) }}" class="d-inline">
                                        @csrf
                                        @if(($quiz->questions_count ?? 0) === 0)
                                            <button class="sg-btn-icon success" title="{{ __('quiz.action_fill_random') }}">
                                                <i class="fas fa-random"></i>
                                            </button>
                                        @else
                                            <span class="sg-btn-icon success sg-btn-icon--disabled" title="{{ __('quiz.tooltip_already_has_questions') }}">
                                                <i class="fas fa-random"></i>
                                            </span>
                                        @endif
                                    </form>
                                @endif

                                @if(auth()->user()->isAdmin() && $quiz->isConfirmed())
                                    <a href="{{ route('admin.quizzes.summary', $quiz) }}"
                                       class="sg-btn-icon info" title="{{ __('quiz.action_summary') }}">
                                        <i class="fas fa-chart-bar"></i>
                                    </a>
                                    <a href="{{ route('admin.quizzes.schedule.edit', $quiz) }}"
                                       class="sg-btn-icon" title="{{ __('quiz.action_schedule') }}">
                                        <i class="fas fa-calendar-alt"></i>
                                    </a>
                                @endif

                                @if(auth()->user()->isAdmin() && !$quiz->isConfirmed())
                                    @if($quiz->isPublished())
                                        <form method="POST" action="{{ route('admin.quizzes.unpublish', $quiz) }}" class="d-inline">
                                            @csrf
                                            <button class="sg-btn-icon" title="{{ __('quiz.action_unpublish') }}">
                                                <i class="fas fa-eye-slash"></i>
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('admin.quizzes.publish', $quiz) }}" class="d-inline">
                                            @csrf
                                            <button class="sg-btn-icon success" title="{{ __('quiz.action_publish') }}">
                                                <i class="fas fa-globe"></i>
                                            </button>
                                        </form>
                                    @endif

                                    @if(($quiz->questions_count ?? 0) > 0)
                                        <form method="POST"
                                              action="{{ route('admin.quizzes.confirm', $quiz) }}"
                                              class="d-inline"
                                              onsubmit="return confirm('{{ __('quiz.confirm_confirm_lock') }}');">
                                            @csrf
                                            <button class="sg-btn-icon info" title="{{ __('quiz.action_confirm') }}">
                                                <i class="fas fa-lock"></i>
                                            </button>
                                        </form>
                                    @endif
                                @endif

                                @if(auth()->user()->canDeleteQuiz() && !$quiz->isLocked())
                                    <form method="POST" action="{{ route('admin.quizzes.destroy', $quiz) }}" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button class="sg-btn-icon delete" title="{{ __('quiz.action_delete') }}" onclick="return confirm('{{ __('quiz.confirm_delete') }}')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('js')
    @parent
    <script>
        $('#quiz-table').DataTable({
            pageLength: 25,
            columnDefs: [{ orderable: false, targets: 5 }]
        });
    </script>
@stop
