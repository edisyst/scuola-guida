@extends('layouts.admin')

@section('title', __('instructor.student_title', ['name' => $student->name]))
@section('content_header')@endsection

@section('content')
<div class="sg-wrapper">

    {{-- Banner sola lettura --}}
    <div class="alert alert-info mb-3">
        <i class="fas fa-eye mr-1"></i>
        <strong>{{ __('instructor.readonly_banner') }}</strong>
        {!! __('instructor.readonly_desc', ['name' => '<strong>'.e($student->name).'</strong>']) !!}
    </div>

    <div class="sg-header sg-flex-between">
        <div>
            <h1 class="sg-header-title">
                <i class="fas fa-user-graduate mr-2"></i> {{ $student->name }}
            </h1>
            <p class="sg-header-subtitle sg-mt-1">{{ $student->email }}</p>
        </div>
        <div class="sg-header-actions">
            <a href="{{ route('instructor.students.export-pdf', $student) }}"
               class="sg-btn sg-btn-secondary sg-btn-sm mr-2">
                <i class="fas fa-file-pdf"></i> {{ __('instructor.action_export_pdf') }}
            </a>
            <a href="{{ route('instructor.students.index') }}" class="sg-btn sg-btn-light sg-btn-sm">
                <i class="fas fa-arrow-left"></i> {{ __('instructor.action_back') }}
            </a>
        </div>
    </div>

    @php
        $stats  = $progress['stats'];
        $streak = $progress['streak'];
        $badges = $progress['badges'];
    @endphp

    {{-- Small-box KPI --}}
    <div class="row">
        <div class="col-sm-3">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $stats['total_attempts'] }}</h3>
                    <p>{{ __('instructor.kpi_attempts') }}</p>
                </div>
                <div class="icon"><i class="fas fa-clipboard-list"></i></div>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $stats['pass_rate'] }}%</h3>
                    <p>{{ __('instructor.kpi_pass_rate') }}</p>
                </div>
                <div class="icon"><i class="fas fa-check-circle"></i></div>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $streak['current'] }}</h3>
                    <p>{{ __('instructor.kpi_streak') }}</p>
                </div>
                <div class="icon"><i class="fas fa-fire"></i></div>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ count($badges) }}</h3>
                    <p>{{ __('instructor.kpi_badges') }}</p>
                </div>
                <div class="icon"><i class="fas fa-award"></i></div>
            </div>
        </div>
    </div>

    {{-- Statistiche dettaglio --}}
    <div class="row">
        <div class="col-md-5">
            <div class="sg-card mb-3">
                <div class="sg-card-header">
                    <h3 class="sg-card-title"><i class="fas fa-chart-bar mr-1"></i> {{ __('instructor.stats_title') }}</h3>
                </div>
                <table class="table table-sm mb-0">
                    <tbody>
                        <tr><th>{{ __('instructor.stats_attempts') }}</th><td>{{ $stats['total_attempts'] }}</td></tr>
                        <tr><th>{{ __('instructor.stats_questions') }}</th><td>{{ $stats['total_questions'] }}</td></tr>
                        <tr><th>{{ __('instructor.stats_correct') }}</th><td>{{ $stats['total_correct'] }}</td></tr>
                        <tr><th>{{ __('instructor.stats_avg') }}</th><td>{{ $stats['avg_percentage'] }}%</td></tr>
                        <tr><th>{{ __('instructor.stats_best') }}</th><td>{{ $stats['best_percentage'] }}%</td></tr>
                        <tr><th>{{ __('instructor.stats_passed_failed') }}</th><td>{{ $stats['passed_count'] }} / {{ $stats['failed_count'] }}</td></tr>
                        <tr><th>{{ __('instructor.stats_longest_streak') }}</th><td>{{ $streak['longest'] }} giorni</td></tr>
                        <tr>
                            <th>{{ __('instructor.stats_active_today') }}</th>
                            <td>
                                @if($streak['has_today'])
                                    <span class="sg-badge sg-badge-success"><i class="fas fa-check"></i> {{ __('instructor.stats_active_yes') }}</span>
                                @else
                                    <span class="sg-badge sg-badge-secondary">{{ __('instructor.stats_active_no') }}</span>
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Badge --}}
            <div class="sg-card">
                <div class="sg-card-header">
                    <h3 class="sg-card-title"><i class="fas fa-award mr-1"></i> {{ __('instructor.badges_title') }}</h3>
                </div>
                @if(empty($badges))
                    <div class="text-center py-3">
                        <p class="text-muted mb-0">{{ __('instructor.no_badges') }}</p>
                    </div>
                @else
                    <div class="p-3">
                        @foreach($badges as $badge)
                            <span class="sg-badge sg-badge-warning mr-1 mb-1">
                                <i class="fas fa-award mr-1"></i>
                                {{ $badge['badge_code'] }}
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Ultimi tentativi --}}
        <div class="col-md-7">
            <div class="sg-card">
                <div class="sg-card-header">
                    <h3 class="sg-card-title"><i class="fas fa-history mr-1"></i> {{ __('instructor.attempts_title') }}</h3>
                </div>
                @if(empty($stats['latest_attempts']))
                    <div class="text-center py-4">
                        <p class="text-muted mb-0">{{ __('instructor.no_attempts') }}</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="sg-table">
                            <thead>
                                <tr>
                                    <th>{{ __('instructor.attempt_col_quiz') }}</th>
                                    <th>{{ __('instructor.attempt_col_score') }}</th>
                                    <th>{{ __('instructor.attempt_col_pct') }}</th>
                                    <th>{{ __('instructor.attempt_col_result') }}</th>
                                    <th>{{ __('instructor.attempt_col_date') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($stats['latest_attempts'] as $attempt)
                                    <tr>
                                        <td>{{ $attempt['quiz_title'] }}</td>
                                        <td>{{ $attempt['score'] }}/{{ $attempt['total_questions'] }}</td>
                                        <td>{{ $attempt['percentage'] }}%</td>
                                        <td>
                                            @if($attempt['is_passed'])
                                                <span class="sg-badge sg-badge-success">{{ __('instructor.attempt_passed') }}</span>
                                            @else
                                                <span class="sg-badge sg-badge-danger">{{ __('instructor.attempt_failed') }}</span>
                                            @endif
                                        </td>
                                        <td class="sg-text-muted">
                                            {{ $attempt['created_at']
                                                ? \Carbon\Carbon::parse($attempt['created_at'])->format('d/m/Y H:i')
                                                : '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Note dell'istruttore --}}
    <div class="row">
        <div class="col-md-12">
            <div class="sg-card mt-3">
                <div class="sg-card-header">
                    <h3 class="sg-card-title">
                        <i class="fas fa-sticky-note mr-1"></i> {{ __('instructor.notes_title') }}
                    </h3>
                </div>
                <div class="p-3">
                    {{-- Lista note esistenti --}}
                    @if($notes->isEmpty())
                        <p class="text-muted mb-3">{{ __('instructor.notes_empty') }}</p>
                    @else
                        @foreach($notes as $note)
                            <div class="card card-outline card-info mb-2">
                                <div class="card-body py-2 px-3">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <p class="mb-1" style="white-space: pre-wrap">{{ $note->body }}</p>
                                        <form method="POST"
                                              action="{{ route('instructor.students.notes.destroy', [$student, $note]) }}"
                                              onsubmit="return confirm('{{ __('instructor.note_delete_confirm') }}')"
                                              class="ml-3 flex-shrink-0">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="sg-btn sg-btn-danger sg-btn-sm">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                    <small class="text-muted">
                                        {{ $note->created_at->format('d/m/Y H:i') }}
                                    </small>
                                </div>
                            </div>
                        @endforeach
                    @endif

                    {{-- Form nuova nota --}}
                    <form method="POST" action="{{ route('instructor.students.notes.store', $student) }}" class="mt-3">
                        @csrf
                        <div class="form-group mb-2">
                            <label for="note-body" class="sr-only">Nuova nota</label>
                            <textarea id="note-body"
                                      name="body"
                                      rows="3"
                                      maxlength="2000"
                                      class="form-control @error('body') is-invalid @enderror"
                                      placeholder="{{ __('instructor.note_placeholder') }}">{{ old('body') }}</textarea>
                            @error('body')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <button type="submit" class="sg-btn sg-btn-primary sg-btn-sm">
                            <i class="fas fa-save mr-1"></i> {{ __('instructor.note_save') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
