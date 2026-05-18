<div class="d-flex align-items-start p-3 border-bottom">

    {{-- Colonna sinistra: date --}}
    <div class="me-3 text-center" style="min-width: 80px;">
        @if($quiz->enrollments_open_at)
            <div class="text-muted small">Apertura</div>
            <div class="fw-bold">{{ $quiz->enrollments_open_at->format('d/m') }}</div>
            <div class="text-muted small">{{ $quiz->enrollments_open_at->format('Y') }}</div>
        @else
            <div class="text-muted small">Sempre</div>
            <div class="fw-bold"><i class="fas fa-infinity"></i></div>
            <div class="text-muted small">aperto</div>
        @endif
    </div>

    {{-- Separatore verticale --}}
    <div class="border-start me-3" style="height: 60px;"></div>

    {{-- Colonna centrale: info quiz --}}
    <div class="flex-grow-1">
        <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
            <strong>{{ $quiz->title }}</strong>

            @switch($quiz->enrollment_status)
                @case('upcoming')
                    <span class="badge bg-warning text-dark">
                        Apre il {{ $quiz->enrollments_open_at->format('d/m/Y') }}
                    </span>
                    @break
                @case('open')
                    <span class="badge bg-success">Iscrizioni aperte</span>
                    @break
                @case('closed')
                    <span class="badge bg-secondary">Iscrizioni chiuse</span>
                    @break
            @endswitch

            @if(in_array($quiz->id, $userEnrollmentQuizIds))
                <span class="badge bg-info">
                    <i class="fas fa-check me-1"></i>Iscritto
                </span>
            @endif
        </div>

        <div class="text-muted small">
            @if($quiz->enrollments_close_at)
                <i class="fas fa-calendar-times me-1"></i>
                Chiusura: {{ $quiz->enrollments_close_at->format('d/m/Y H:i') }}
                &nbsp;&bull;&nbsp;
            @endif
            <i class="fas fa-question-circle me-1"></i>{{ $quiz->max_questions }} domande
            &nbsp;&bull;&nbsp;
            <i class="fas fa-clock me-1"></i>{{ $quiz->time_limit }} min
            &nbsp;&bull;&nbsp;
            <i class="fas fa-times-circle me-1"></i>Max {{ $quiz->max_errors }} errori
        </div>

        @if($quiz->enrollment_status === 'upcoming')
            <div class="mt-1 small text-warning"
                 x-data="countdown({{ $quiz->enrollments_open_at->timestamp }})"
                 x-init="start()">
                <i class="fas fa-hourglass-half me-1"></i>
                Mancano: <span x-text="display"></span>
            </div>
        @endif
    </div>

    {{-- Colonna destra: azione --}}
    <div class="ms-3 d-flex flex-column align-items-end gap-1">
        @if($quiz->enrollment_status === 'open' && !in_array($quiz->id, $userEnrollmentQuizIds))
            @if($canEnroll)
                <form method="POST" action="{{ route('quiz.enrollments.store', $quiz) }}">
                    @csrf
                    <button type="submit" class="sg-btn sg-btn-outline sg-btn-sm">
                        <i class="fas fa-paper-plane"></i> Richiedi iscrizione
                    </button>
                </form>
            @else
                <a href="{{ route('profile.edit') }}" class="sg-btn sg-btn-light sg-btn-sm">
                    <i class="fas fa-id-card"></i> Completa profilo
                </a>
            @endif
        @elseif($quiz->enrollment_status === 'upcoming')
            <span class="text-muted small">Disponibile a breve</span>
        @elseif($quiz->enrollment_status === 'closed')
            <span class="text-muted small">Chiusa</span>
        @endif
    </div>

</div>
