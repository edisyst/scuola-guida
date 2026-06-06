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

    {{-- Guide pratiche --}}
    {{-- Variabili richieste dal controller: $drivingProgress, $drivingSessions, $drivingModules, $hasPassedTheory --}}
    <div class="row mt-3">
        <div class="col-md-12">
            <div class="sg-card">
                <div class="sg-card-header">
                    <h3 class="sg-card-title">
                        <i class="fas fa-car mr-1"></i> {{ __('driving.card_title') }}
                    </h3>
                </div>
                <div class="p-3">

                    {{-- Avviso teoria non superata --}}
                    @if(!$hasPassedTheory)
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle mr-1"></i>
                            {{ __('driving.theory_warning') }}
                        </div>
                    @endif

                    {{-- Avanzamento per modulo --}}
                    @if(empty($drivingProgress['modules']))
                        <p class="text-muted">{{ __('driving.session_none') }}</p>
                    @else
                        @foreach($drivingProgress['modules'] as $item)
                            <div class="mb-3">
                                <div class="d-flex justify-content-between">
                                    <strong>{{ $item['module']->code }} — {{ $item['module']->name }}</strong>
                                    <span>
                                        {{ $item['completed_hours'] }} / {{ $item['required_hours'] }} h
                                        @if($item['completed'])
                                            <span class="sg-badge sg-badge-success ml-1">
                                                {{ __('driving.progress_completed') }}
                                            </span>
                                        @endif
                                    </span>
                                </div>
                                @php
                                    $pct = $item['required_hours'] > 0
                                        ? min(100, round(($item['completed_hours'] / $item['required_hours']) * 100))
                                        : 0;
                                @endphp
                                <div class="progress mt-1" style="height: 8px;">
                                    <div class="progress-bar {{ $item['completed'] ? 'bg-success' : 'bg-primary' }}"
                                         style="width: {{ $pct }}%"
                                         aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100">
                                    </div>
                                </div>
                                <small class="text-muted">
                                    {{ $item['sessions_count'] }} {{ __('driving.progress_sessions') }}
                                </small>
                            </div>
                        @endforeach

                        {{-- Riepilogo totale --}}
                        <div class="alert alert-light border mt-2">
                            <strong>
                                Totale: {{ $drivingProgress['total_completed'] }} /
                                {{ $drivingProgress['total_required'] }} h
                                — {{ $drivingProgress['percentage'] }}%
                            </strong>
                        </div>

                        {{-- Pulsante download riepilogo PDF (se completato) --}}
                        @if($drivingProgress['all_completed'] && isset($drivingSessions) && $drivingSessions->isNotEmpty())
                            <div class="mt-3">
                                <a href="{{ route('driving.attestation.download', $student) }}"
                                   class="sg-btn sg-btn-info sg-btn-sm"
                                   target="_blank">
                                    <i class="fas fa-file-pdf mr-1"></i> {{ __('driving.download_attestation') }}
                                </a>
                            </div>
                        @endif
                    @endif

                    {{-- Ultime sessioni registrate (variabile $drivingSessions opzionale) --}}
                    @if(isset($drivingSessions) && $drivingSessions->isNotEmpty())
                        <h5 class="mt-3">{{ __('driving.title_sessions') }}</h5>
                        <div class="table-responsive">
                            <table class="sg-table">
                                <thead>
                                    <tr>
                                        <th>{{ __('driving.session_date') }}</th>
                                        <th>{{ __('driving.session_module') }}</th>
                                        <th>{{ __('driving.session_duration') }}</th>
                                        <th>{{ __('driving.session_notes') }}</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($drivingSessions as $session)
                                        <tr>
                                            <td>{{ $session->conducted_at->format('d/m/Y') }}</td>
                                            <td>{{ $session->drivingModule->code }}</td>
                                            <td>{{ $session->duration_minutes }} min</td>
                                            <td>{{ Str::limit($session->notes, 50) }}</td>
                                            <td>
                                                <form method="POST"
                                                      action="{{ route('driving.sessions.destroy', [$student, $session]) }}"
                                                      style="display: inline;"
                                                      onsubmit="return confirm('{{ __('driving.session_delete_confirm') }}')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="sg-btn sg-btn-danger sg-btn-sm">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    {{-- Form registrazione sessione (visibile solo se lo studente ha moduli disponibili) --}}
                    @if(isset($drivingModules) && $drivingModules->isNotEmpty())
                        <h5 class="mt-4">{{ __('driving.register_session') }}</h5>
                        <form method="POST" action="{{ route('driving.sessions.store', $student) }}">
                            @csrf
                            <div class="row">
                                <div class="col-md-4 form-group">
                                    <label>{{ __('driving.field_module') }}</label>
                                    <select name="driving_module_id"
                                            class="form-control @error('driving_module_id') is-invalid @enderror"
                                            required>
                                        <option value="">—</option>
                                        @foreach($drivingModules as $mod)
                                            <option value="{{ $mod->id }}"
                                                {{ old('driving_module_id') == $mod->id ? 'selected' : '' }}>
                                                {{ $mod->code }} — {{ $mod->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('driving_module_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-3 form-group">
                                    <label>{{ __('driving.field_conducted_at') }}</label>
                                    <input type="date"
                                           name="conducted_at"
                                           class="form-control @error('conducted_at') is-invalid @enderror"
                                           value="{{ old('conducted_at', date('Y-m-d')) }}"
                                           max="{{ date('Y-m-d') }}"
                                           required>
                                    @error('conducted_at')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-2 form-group">
                                    <label>{{ __('driving.field_duration') }}</label>
                                    <input type="number"
                                           name="duration_minutes"
                                           class="form-control @error('duration_minutes') is-invalid @enderror"
                                           value="{{ old('duration_minutes', 60) }}"
                                           min="15"
                                           max="120"
                                           required>
                                    @error('duration_minutes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-3 form-group">
                                    <label>{{ __('driving.field_notes') }}</label>
                                    <input type="text"
                                           name="notes"
                                           class="form-control"
                                           value="{{ old('notes') }}"
                                           maxlength="1000">
                                </div>
                            </div>
                            <button type="submit" class="sg-btn sg-btn-primary sg-btn-sm">
                                <i class="fas fa-save mr-1"></i> {{ __('driving.register_session') }}
                            </button>
                        </form>
                    @elseif(isset($drivingModules))
                        {{-- $drivingModules è presente ma vuota: nessun tipo patente associato --}}
                        <p class="text-muted mt-3">{{ __('driving.no_license_type') }}</p>
                    @endif

                </div>
            </div>
        </div>
    </div>

</div>
@endsection
