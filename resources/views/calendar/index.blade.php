@extends('layouts.admin')

@section('title', 'Calendario sessioni')
@section('content_header')@endsection

@section('content')
<div class="sg-wrapper">

    <div class="sg-header">
        <p class="sg-header-subtitle">Esami ufficiali</p>
        <h1 class="sg-header-title"><i class="fas fa-calendar-alt mr-2"></i> Calendario sessioni</h1>
    </div>

    {{-- SEZIONE 1: Prossime sessioni --}}
    <div class="sg-card sg-mb-3" style="border-top: 3px solid #ffc107;">
        <div class="sg-card-header" style="background: #fff8e1;">
            <h2 class="sg-card-header-title" style="color: #856404;">
                <i class="fas fa-clock me-1"></i> Prossime sessioni
            </h2>
        </div>
        <div class="sg-card-body p-0">
            @forelse($upcoming as $quiz)
                @include('calendar._quiz-row', ['quiz' => $quiz, 'userEnrollmentQuizIds' => $userEnrollmentQuizIds])
            @empty
                <p class="sg-text-muted p-3 mb-0">Nessuna sessione programmata.</p>
            @endforelse
        </div>
    </div>

    {{-- SEZIONE 2: Iscrizioni aperte --}}
    <div class="sg-card sg-mb-3" style="border-top: 3px solid #28a745;">
        <div class="sg-card-header" style="background: #f0fff4;">
            <h2 class="sg-card-header-title" style="color: #155724;">
                <i class="fas fa-door-open me-1"></i> Iscrizioni aperte
            </h2>
        </div>
        <div class="sg-card-body p-0">
            @forelse($open as $quiz)
                @include('calendar._quiz-row', ['quiz' => $quiz, 'userEnrollmentQuizIds' => $userEnrollmentQuizIds])
            @empty
                <p class="sg-text-muted p-3 mb-0">Nessuna sessione con iscrizioni aperte al momento.</p>
            @endforelse
        </div>
    </div>

    {{-- SEZIONE 3: Sessioni chiuse (ultimi 10) --}}
    <div class="sg-card" style="border-top: 3px solid #6c757d; opacity: 0.85;">
        <div class="sg-card-header" style="background: #f8f9fa;">
            <h2 class="sg-card-header-title" style="color: #495057;">
                <i class="fas fa-lock me-1"></i> Sessioni chiuse
                <small class="text-muted ms-1" style="font-size:0.8rem;">(ultime 10)</small>
            </h2>
        </div>
        <div class="sg-card-body p-0">
            @forelse($closed as $quiz)
                @include('calendar._quiz-row', ['quiz' => $quiz, 'userEnrollmentQuizIds' => $userEnrollmentQuizIds])
            @empty
                <p class="sg-text-muted p-3 mb-0">Nessuna sessione chiusa.</p>
            @endforelse
        </div>
    </div>

</div>
@endsection

@push('js')
<script>
function countdown(targetTimestamp) {
    return {
        display: '',
        intervalId: null,
        start() {
            this.update();
            this.intervalId = setInterval(() => this.update(), 1000);
        },
        update() {
            const diff = targetTimestamp - Math.floor(Date.now() / 1000);
            if (diff <= 0) {
                this.display = 'Aperto ora';
                clearInterval(this.intervalId);
                return;
            }
            const d = Math.floor(diff / 86400);
            const h = Math.floor((diff % 86400) / 3600);
            const m = Math.floor((diff % 3600) / 60);
            const s = diff % 60;
            this.display = d > 0
                ? `${d}g ${h}h ${m}m`
                : `${h}h ${m}m ${s}s`;
        }
    };
}
</script>
@endpush
