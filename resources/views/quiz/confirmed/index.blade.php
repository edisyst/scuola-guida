@extends('layouts.admin')

@section('title', 'Quiz disponibili')
@section('content_header')@endsection

@section('content')
<div class="sg-wrapper">

    <div class="sg-header">
        <p class="sg-header-subtitle">{{ __('viewer.quiz.official') }}</p>
        <h1 class="sg-header-title"><i class="fas fa-clipboard-check mr-2"></i> {{ __('viewer.quiz.available') }}</h1>
    </div>

    @if($user->isViewer() && !$canEnroll)
        <div class="alert alert-warning sg-mb-3">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>{{ __('viewer.quiz.registration_required') }}</strong>
            Per iscriverti agli esami ufficiali devi prima inviare i tuoi dati anagrafici dal
            <a href="{{ route('profile.edit') }}">tuo profilo</a> ed essere approvato dall'amministratore.
            @if($user->isRegistrationPending())
                {{ __('viewer.quiz.registration_pending') }}
            @elseif($user->isRegistrationRejected())
                {{ __('viewer.quiz.registration_rejected') }}
            @endif
            {!! __('viewer.quiz.practice_meanwhile') !!}
        </div>
    @elseif(!$user->isViewer())
        <div class="alert alert-info sg-mb-3">
            <i class="fas fa-eye"></i>
            <strong>{{ __('viewer.quiz.readonly_admin') }}</strong>
            {{ $user->isAdmin() ? __('viewer.quiz.readonly_note_admin') : __('viewer.quiz.readonly_note_editor') }}
        </div>
    @endif

    <div class="sg-card">
        @if($quizzes->isEmpty())
            <div class="sg-table-empty">{{ __('viewer.quiz.no_quizzes') }}</div>
        @else
            <div class="table-responsive">
                <table class="sg-table">
                    <thead>
                        <tr>
                            <th>{{ __('viewer.quiz.title_col') }}</th>
                            <th>{{ __('viewer.quiz.questions_col') }}</th>
                            <th>{{ __('viewer.quiz.time_col') }}</th>
                            @if($user->isViewer())
                                <th>{{ __('viewer.quiz.enrollment_status') }}</th>
                                <th class="text-right">{{ __('viewer.quiz.actions') }}</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($quizzes as $quiz)
                            @php
                                $userEnrollments = $enrollments->get($quiz->id) ?? collect();
                                $latest          = $userEnrollments->first();
                                $active          = $userEnrollments->firstWhere(fn ($e) => in_array($e->status, [
                                    \App\Models\QuizEnrollment::STATUS_PENDING,
                                    \App\Models\QuizEnrollment::STATUS_APPROVED,
                                ]));
                            @endphp
                            <tr>
                                <td><strong>{{ $quiz->title }}</strong></td>
                                <td>{{ $quiz->questions_count }}</td>
                                <td class="sg-text-muted">
                                    {{ $quiz->time_limit ? gmdate('i\'', $quiz->time_limit) : '—' }}
                                </td>
                                @if($user->isViewer())
                                    <td>
                                        @if($active && $active->isPending())
                                            <span class="sg-badge sg-badge-warning">{{ __('viewer.quiz.status_pending') }}</span>
                                        @elseif($active && $active->isApproved())
                                            <span class="sg-badge sg-badge-success">{{ __('viewer.quiz.status_approved') }}</span>
                                        @elseif($latest && $latest->isCompleted())
                                            <span class="sg-badge sg-badge-info">{{ __('viewer.quiz.status_completed') }}</span>
                                        @elseif($latest && $latest->isRejected())
                                            <span class="sg-badge sg-badge-danger">{{ __('viewer.quiz.status_rejected') }}</span>
                                        @else
                                            <span class="sg-text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        @if($active && $active->isApproved())
                                            <a href="{{ route('quiz.play', $quiz) }}"
                                               class="sg-btn sg-btn-primary sg-btn-sm"
                                               onclick="return confirm('{{ __('viewer.quiz.play_confirm') }}');">
                                                <i class="fas fa-play"></i> {{ __('viewer.quiz.play_btn') }}
                                            </a>
                                        @elseif($active && $active->isPending())
                                            <span class="sg-text-muted"><i class="fas fa-hourglass-half"></i> {{ __('viewer.quiz.wait_approval') }}</span>
                                        @elseif($latest && $latest->isCompleted())
                                            <span class="sg-text-muted">{{ __('viewer.quiz.already_used') }}</span>
                                        @elseif($quiz->enrollmentsNotYetOpen())
                                            <span class="sg-text-muted">
                                                <i class="fas fa-clock"></i>
                                                Iscrizioni aperte dal {{ $quiz->enrollments_open_at->translatedFormat('d F Y \a\l\l\e H:i') }}
                                            </span>
                                        @elseif($quiz->enrollmentsClosed())
                                            <span class="sg-text-muted">
                                                <i class="fas fa-lock"></i> {{ __('viewer.quiz.enrollments_closed') }}
                                            </span>
                                        @elseif(!$canEnroll)
                                            <a href="{{ route('profile.edit') }}" class="sg-btn sg-btn-light sg-btn-sm">
                                                <i class="fas fa-id-card"></i> {{ __('viewer.quiz.complete_profile') }}
                                            </a>
                                        @else
                                            <form method="POST" action="{{ route('quiz.enrollments.store', $quiz) }}" class="d-inline">
                                                @csrf
                                                <button class="sg-btn sg-btn-outline sg-btn-sm">
                                                    <i class="fas fa-paper-plane"></i> {{ __('viewer.quiz.request_enrollment') }}
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
