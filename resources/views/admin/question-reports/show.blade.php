@extends('layouts.admin')

@section('title', 'Segnalazione #' . $report->id)
@section('content_header')@endsection

@section('content')
@php
    $imageUrl = $report->question && $report->question->image
        ? \Illuminate\Support\Facades\Storage::url($report->question->image)
        : null;
@endphp

<div class="sg-wrapper">

    <div class="sg-header sg-flex-between">
        <div>
            <p class="sg-header-subtitle">Moderazione contenuti</p>
            <h1 class="sg-header-title">
                <i class="fas fa-flag mr-2"></i> Segnalazione #{{ $report->id }}
            </h1>
        </div>
        <div>
            <a href="{{ route('admin.question-reports.index') }}"
               class="sg-btn sg-btn-light sg-btn-sm">
                <i class="fas fa-arrow-left"></i> Torna all'elenco
            </a>
        </div>
    </div>

    <div class="row">
        {{-- ── Colonna sinistra: la domanda ──────────────────── --}}
        <div class="col-12 col-md-7 mb-3">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-question-circle mr-1"></i> Domanda segnalata
                    </h3>
                </div>
                <div class="card-body">
                    @if($report->question)
                        @if($report->question->category)
                            <span class="badge badge-info mb-2">
                                {{ $report->question->category->name }}
                            </span>
                        @endif

                        <p class="font-weight-bold mb-3">{{ $report->question->question }}</p>

                        @if($imageUrl)
                            <div class="text-center mb-3">
                                <img src="{{ $imageUrl }}"
                                     alt="Immagine domanda"
                                     class="img-fluid rounded shadow-sm"
                                     style="max-height: 280px;">
                            </div>
                        @endif

                        <p class="mb-0">
                            Risposta corretta:
                            @if($report->question->is_true)
                                <span class="badge badge-success">VERO</span>
                            @else
                                <span class="badge badge-danger">FALSO</span>
                            @endif
                        </p>
                    @else
                        <em class="text-muted">La domanda associata è stata eliminata.</em>
                    @endif
                </div>
                @if($report->question && auth()->user()->canEditQuestion())
                    <div class="card-footer text-right">
                        <a href="{{ route('admin.questions.edit', $report->question) }}"
                           class="sg-btn sg-btn-warning sg-btn-sm">
                            <i class="fas fa-edit"></i> Modifica domanda
                        </a>
                    </div>
                @endif
            </div>
        </div>

        {{-- ── Colonna destra: il report ─────────────────────── --}}
        <div class="col-12 col-md-5 mb-3">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-info-circle mr-1"></i> Dettagli segnalazione
                    </h3>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Segnalante</dt>
                        <dd class="col-sm-8">
                            {{ $report->user?->name ?? '—' }}<br>
                            <small class="text-muted">{{ $report->user?->email }}</small>
                        </dd>

                        <dt class="col-sm-4">Data segnalazione</dt>
                        <dd class="col-sm-8">
                            {{ $report->created_at->format('d/m/Y H:i') }}
                        </dd>

                        <dt class="col-sm-4">Tipo</dt>
                        <dd class="col-sm-8">
                            <span class="badge badge-info">
                                {{ $types[$report->type] ?? $report->type }}
                            </span>
                        </dd>

                        <dt class="col-sm-4">Stato</dt>
                        <dd class="col-sm-8">
                            @switch($report->status)
                                @case('pending')
                                    <span class="badge badge-warning">In attesa</span>
                                    @break
                                @case('accepted')
                                    <span class="badge badge-success">Accettata</span>
                                    @break
                                @case('rejected')
                                    <span class="badge badge-secondary">Rifiutata</span>
                                    @break
                            @endswitch
                        </dd>
                    </dl>

                    <hr>

                    <h6 class="text-uppercase text-muted small mb-2">Testo segnalazione</h6>
                    <div class="alert alert-warning mb-0">
                        {{ $report->body }}
                    </div>

                    @if($report->status !== 'pending')
                        <hr>
                        <h6 class="text-uppercase text-muted small mb-2">Risoluzione</h6>
                        <dl class="row mb-0">
                            <dt class="col-sm-4">Risolta da</dt>
                            <dd class="col-sm-8">{{ $report->resolvedBy?->name ?? '—' }}</dd>

                            <dt class="col-sm-4">Risolta il</dt>
                            <dd class="col-sm-8">
                                {{ $report->resolved_at?->format('d/m/Y H:i') ?? '—' }}
                            </dd>

                            @if($report->admin_note)
                                <dt class="col-sm-4">Nota admin</dt>
                                <dd class="col-sm-8">{{ $report->admin_note }}</dd>
                            @endif
                        </dl>
                    @endif
                </div>
            </div>

            @if($report->status === 'pending')
                <div class="card mt-3"
                     x-data="{ note: '' }">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-gavel mr-1"></i> Gestisci segnalazione
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Nota per il segnalante (opzionale)</label>
                            <textarea x-model="note"
                                      maxlength="1000"
                                      rows="3"
                                      class="form-control"
                                      placeholder="Motivo dell'accettazione o rifiuto..."></textarea>
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            <form method="POST"
                                  action="{{ route('admin.question-reports.accept', $report) }}"
                                  class="m-0">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="admin_note" :value="note">
                                <button type="submit" class="sg-btn sg-btn-success">
                                    <i class="fas fa-check"></i> Accetta
                                </button>
                            </form>

                            <form method="POST"
                                  action="{{ route('admin.question-reports.reject', $report) }}"
                                  class="m-0">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="admin_note" :value="note">
                                <button type="submit" class="sg-btn sg-btn-secondary">
                                    <i class="fas fa-times"></i> Rifiuta
                                </button>
                            </form>

                            <form method="POST"
                                  action="{{ route('admin.question-reports.destroy', $report) }}"
                                  class="m-0 ml-auto"
                                  onsubmit="return confirm('Eliminare definitivamente questa segnalazione?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="sg-btn sg-btn-light sg-btn-sm">
                                    <i class="fas fa-trash text-danger"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
