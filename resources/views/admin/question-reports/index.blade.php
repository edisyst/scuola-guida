@extends('layouts.admin')

@section('title', 'Segnalazioni domande')
@section('content_header')@endsection

@section('content')
<div class="sg-wrapper-fluid">

    <div class="sg-header sg-flex-between">
        <div>
            <p class="sg-header-subtitle">Moderazione contenuti</p>
            <h1 class="sg-header-title">
                <i class="fas fa-flag mr-2"></i> Segnalazioni domande
            </h1>
        </div>
    </div>

    {{-- ── KPI ──────────────────────────────────────────────── --}}
    <div class="row sg-mb-3">
        <div class="col-12 col-md-4 mb-2">
            <div class="sg-status-box sg-status-box--warning">
                <div class="sg-status-box-value">{{ $stats['pending'] }}</div>
                <div class="sg-status-box-label"><i class="fas fa-clock mr-1"></i> In attesa</div>
                <div class="sg-status-box-action">
                    <a href="{{ route('admin.question-reports.index', ['status' => 'pending']) }}"
                       class="sg-btn sg-btn-light sg-btn-sm">
                        <i class="fas fa-filter"></i> Filtra
                    </a>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4 mb-2">
            <div class="sg-status-box sg-status-box--success">
                <div class="sg-status-box-value">{{ $stats['accepted'] }}</div>
                <div class="sg-status-box-label"><i class="fas fa-check mr-1"></i> Accettate</div>
                <div class="sg-status-box-action">
                    <a href="{{ route('admin.question-reports.index', ['status' => 'accepted']) }}"
                       class="sg-btn sg-btn-light sg-btn-sm">
                        <i class="fas fa-filter"></i> Filtra
                    </a>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4 mb-2">
            <div class="sg-status-box sg-status-box--muted">
                <div class="sg-status-box-value">{{ $stats['rejected'] }}</div>
                <div class="sg-status-box-label"><i class="fas fa-times mr-1"></i> Rifiutate</div>
                <div class="sg-status-box-action">
                    <a href="{{ route('admin.question-reports.index', ['status' => 'rejected']) }}"
                       class="sg-btn sg-btn-light sg-btn-sm">
                        <i class="fas fa-filter"></i> Filtra
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Filtri ───────────────────────────────────────────── --}}
    <div class="sg-card sg-mb-3">
        <div class="sg-card-body" style="padding:1rem 1.25rem;">
            <form method="GET" action="{{ route('admin.question-reports.index') }}"
                  class="row g-2 align-items-end">
                <div class="col-12 col-md-3">
                    <label class="form-label form-label-sm mb-1">Stato</label>
                    <select name="status" class="sg-form-control form-control">
                        <option value="">Tutti</option>
                        @foreach(\App\Models\QuestionReport::statuses() as $key => $label)
                            <option value="{{ $key }}" @selected(request('status') === $key)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label form-label-sm mb-1">Tipo</label>
                    <select name="type" class="sg-form-control form-control">
                        <option value="">Tutti</option>
                        @foreach($types as $key => $label)
                            <option value="{{ $key }}" @selected(request('type') === $key)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label form-label-sm mb-1">Domanda (ID)</label>
                    <input type="number" name="question_id" min="1"
                           value="{{ request('question_id') }}"
                           class="sg-form-control form-control"
                           placeholder="Es. 1234">
                </div>
                <div class="col-12 col-md-2 d-flex gap-2">
                    <button type="submit" class="sg-btn sg-btn-primary sg-btn-sm">
                        <i class="fas fa-filter"></i> Filtra
                    </button>
                    <a href="{{ route('admin.question-reports.index') }}"
                       class="sg-btn sg-btn-light sg-btn-sm">Reset</a>
                </div>
            </form>
        </div>
    </div>

    {{-- ── Tabella ──────────────────────────────────────────── --}}
    <div class="sg-card">
        @if($reports->isEmpty())
            <div class="sg-table-empty">Nessuna segnalazione trovata.</div>
        @else
            <div class="table-responsive">
                <table class="sg-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Domanda</th>
                            <th>Tipo</th>
                            <th>Segnalante</th>
                            <th>Data</th>
                            <th>Stato</th>
                            <th class="text-right">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reports as $r)
                            <tr>
                                <td class="sg-text-muted">{{ $r->id }}</td>
                                <td>
                                    @if($r->question)
                                        <span title="{{ $r->question->question }}">
                                            #{{ $r->question->id }} —
                                            {{ \Illuminate\Support\Str::limit($r->question->question, 60) }}
                                        </span>
                                    @else
                                        <em class="sg-text-muted">Domanda eliminata</em>
                                    @endif
                                </td>
                                <td>
                                    <span class="sg-badge sg-badge-info">
                                        {{ $types[$r->type] ?? $r->type }}
                                    </span>
                                </td>
                                <td>
                                    {{ $r->user?->name ?? '—' }}<br>
                                    <small class="sg-text-muted">{{ $r->user?->email }}</small>
                                </td>
                                <td class="sg-text-muted">
                                    {{ $r->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td>
                                    @switch($r->status)
                                        @case('pending')
                                            <span class="sg-badge sg-badge--pending">In attesa</span>
                                            @break
                                        @case('accepted')
                                            <span class="sg-badge sg-badge--accepted">Accettata</span>
                                            @break
                                        @case('rejected')
                                            <span class="sg-badge sg-badge--rejected">Rifiutata</span>
                                            @break
                                    @endswitch
                                </td>
                                <td class="text-right">
                                    <a href="{{ route('admin.question-reports.show', $r) }}"
                                       class="sg-btn sg-btn-light sg-btn-sm">
                                        <i class="fas fa-eye"></i> Dettaglio
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="sg-card-section">
                {{ $reports->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
