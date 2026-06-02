@extends('layouts.admin')

@section('title', 'Segnalazioni domande')
@section('content_header')@endsection

@section('content')
<div class="sg-wrapper">

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
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $stats['pending'] }}</h3>
                    <p>In attesa</p>
                </div>
                <div class="icon"><i class="fas fa-clock"></i></div>
                <a href="{{ route('admin.question-reports.index', ['status' => 'pending']) }}"
                   class="small-box-footer">
                    Filtra <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        <div class="col-12 col-md-4 mb-2">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $stats['accepted'] }}</h3>
                    <p>Accettate</p>
                </div>
                <div class="icon"><i class="fas fa-check"></i></div>
                <a href="{{ route('admin.question-reports.index', ['status' => 'accepted']) }}"
                   class="small-box-footer">
                    Filtra <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        <div class="col-12 col-md-4 mb-2">
            <div class="small-box bg-secondary">
                <div class="inner">
                    <h3>{{ $stats['rejected'] }}</h3>
                    <p>Rifiutate</p>
                </div>
                <div class="icon"><i class="fas fa-times"></i></div>
                <a href="{{ route('admin.question-reports.index', ['status' => 'rejected']) }}"
                   class="small-box-footer">
                    Filtra <i class="fas fa-arrow-circle-right"></i>
                </a>
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
                            <tr class="{{ $r->status === 'pending' ? 'table-warning' : '' }}">
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
                                    <span class="badge badge-info">
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
                                            <span class="badge badge-warning">In attesa</span>
                                            @break
                                        @case('accepted')
                                            <span class="badge badge-success">Accettata</span>
                                            @break
                                        @case('rejected')
                                            <span class="badge badge-secondary">Rifiutata</span>
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
