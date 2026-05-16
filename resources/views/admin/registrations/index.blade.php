@extends('layouts.admin')

@section('title', 'Iscrizioni anagrafiche')
@section('content_header')@endsection

@section('content')
<div class="sg-wrapper">

    <div class="sg-header sg-flex-between">
        <div>
            <p class="sg-header-subtitle">Gestione utenti</p>
            <h1 class="sg-header-title"><i class="fas fa-id-card mr-2"></i> Iscrizioni anagrafiche</h1>
        </div>
        @if($pendingCount > 0)
            <span class="sg-badge sg-badge-warning">
                <i class="fas fa-clock"></i> {{ $pendingCount }} in attesa
            </span>
        @endif
    </div>

    <div class="sg-card sg-mb-3">
        <div class="sg-card-body sg-flex" style="gap:8px;flex-wrap:wrap;">
            <a href="{{ route('admin.registrations.index') }}"
               class="sg-btn sg-btn-sm {{ !$status ? 'sg-btn-primary' : 'sg-btn-light' }}">Tutte</a>
            @foreach(\App\Models\User::REG_STATUSES as $key => $label)
                @if($key === \App\Models\User::REG_NONE) @continue @endif
                <a href="{{ route('admin.registrations.index', ['status' => $key]) }}"
                   class="sg-btn sg-btn-sm {{ $status === $key ? 'sg-btn-primary' : 'sg-btn-light' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>

    <div class="sg-card">
        @if($registrations->isEmpty())
            <div class="sg-table-empty">Nessuna richiesta di iscrizione trovata.</div>
        @else
            <div class="table-responsive">
                <table class="sg-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Utente</th>
                            <th>Email</th>
                            <th>Codice fiscale</th>
                            <th>Stato</th>
                            <th>Inviata il</th>
                            <th style="text-align:right;">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($registrations as $u)
                            <tr>
                                <td class="sg-text-muted">{{ $u->id }}</td>
                                <td><strong>{{ $u->fullAnagraphicName() }}</strong></td>
                                <td class="sg-text-muted">{{ $u->email }}</td>
                                <td class="sg-text-muted">{{ $u->fiscal_code ?? '—' }}</td>
                                <td>@include('profile.partials.registration-status-badge', ['user' => $u])</td>
                                <td class="sg-text-muted">
                                    {{ $u->registration_submitted_at?->format('d/m/Y H:i') ?? '—' }}
                                </td>
                                <td style="text-align:right;">
                                    <a href="{{ route('admin.registrations.show', $u) }}"
                                       class="sg-btn sg-btn-light sg-btn-sm">
                                        <i class="fas fa-eye"></i> Dettagli
                                    </a>
                                    @if($u->isRegistrationPending())
                                        <form method="POST" action="{{ route('admin.registrations.approve', $u) }}"
                                              style="display:inline;"
                                              onsubmit="return confirm('Approvare l\'iscrizione di {{ $u->fullAnagraphicName() }}?');">
                                            @csrf
                                            <button class="sg-btn sg-btn-success sg-btn-sm">
                                                <i class="fas fa-check"></i> Approva
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="sg-card-section">
                {{ $registrations->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
