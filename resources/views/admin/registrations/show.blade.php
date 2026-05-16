@extends('layouts.admin')

@section('title', 'Richiesta iscrizione — ' . $user->fullAnagraphicName())
@section('content_header')@endsection

@section('content')
<div class="sg-wrapper-sm">

    <div class="sg-header sg-flex-between">
        <div>
            <p class="sg-header-subtitle">Iscrizione anagrafica</p>
            <h1 class="sg-header-title">
                <i class="fas fa-user mr-2"></i> {{ $user->fullAnagraphicName() }}
            </h1>
        </div>
        <div>
            @include('profile.partials.registration-status-badge', ['user' => $user])
        </div>
    </div>

    <div class="sg-mb-3">
        <a href="{{ route('admin.registrations.index') }}" class="sg-btn sg-btn-light sg-btn-sm">
            <i class="fas fa-arrow-left"></i> Torna alla lista
        </a>
    </div>

    <div class="sg-card sg-mb-3">
        <div class="sg-card-header">
            <h2 class="sg-card-header-title">Dati anagrafici</h2>
        </div>
        <div class="sg-card-body">
            <dl class="row mb-0">
                <dt class="col-sm-4">Nome</dt>
                <dd class="col-sm-8">{{ $user->first_name ?? '—' }}</dd>

                <dt class="col-sm-4">Cognome</dt>
                <dd class="col-sm-8">{{ $user->last_name ?? '—' }}</dd>

                <dt class="col-sm-4">Email account</dt>
                <dd class="col-sm-8">{{ $user->email }}</dd>

                <dt class="col-sm-4">Indirizzo</dt>
                <dd class="col-sm-8">{{ $user->address ?? '—' }}</dd>

                <dt class="col-sm-4">Data di nascita</dt>
                <dd class="col-sm-8">{{ optional($user->birth_date)->format('d/m/Y') ?? '—' }}</dd>

                <dt class="col-sm-4">Luogo di nascita</dt>
                <dd class="col-sm-8">{{ $user->birth_place ?? '—' }}</dd>

                <dt class="col-sm-4">Codice fiscale</dt>
                <dd class="col-sm-8"><code>{{ $user->fiscal_code ?? '—' }}</code></dd>

                <dt class="col-sm-4">Documento di identità</dt>
                <dd class="col-sm-8">
                    @if($documentUrl)
                        <a href="{{ $documentUrl }}" target="_blank" rel="noopener" class="sg-btn sg-btn-light sg-btn-sm">
                            <i class="fas fa-file-alt"></i> Visualizza documento
                        </a>
                    @else
                        <span class="sg-text-muted">— nessun file caricato</span>
                    @endif
                </dd>
            </dl>
        </div>
    </div>

    <div class="sg-card sg-mb-3">
        <div class="sg-card-header">
            <h2 class="sg-card-header-title">Storico revisione</h2>
        </div>
        <div class="sg-card-body">
            <dl class="row mb-0">
                <dt class="col-sm-4">Inviata il</dt>
                <dd class="col-sm-8">
                    {{ $user->registration_submitted_at?->format('d/m/Y H:i') ?? '—' }}
                </dd>

                <dt class="col-sm-4">Revisionata il</dt>
                <dd class="col-sm-8">
                    {{ $user->registration_reviewed_at?->format('d/m/Y H:i') ?? '—' }}
                </dd>

                <dt class="col-sm-4">Revisionata da</dt>
                <dd class="col-sm-8">{{ $user->registrationReviewer->name ?? '—' }}</dd>

                @if($user->registration_rejection_reason)
                    <dt class="col-sm-4">Motivo rifiuto</dt>
                    <dd class="col-sm-8">{{ $user->registration_rejection_reason }}</dd>
                @endif
            </dl>
        </div>
    </div>

    @if($user->isRegistrationPending())
        <div class="sg-card">
            <div class="sg-card-header">
                <h2 class="sg-card-header-title">Decisione</h2>
            </div>
            <div class="sg-card-body sg-flex flex-wrap" style="gap:8px;">
                <form method="POST" action="{{ route('admin.registrations.approve', $user) }}"
                      onsubmit="return confirm('Approvare definitivamente l\'iscrizione anagrafica di {{ $user->fullAnagraphicName() }}?');">
                    @csrf
                    <button class="sg-btn sg-btn-success">
                        <i class="fas fa-check"></i> Approva iscrizione
                    </button>
                </form>

                <form method="POST" action="{{ route('admin.registrations.reject', $user) }}"
                      style="flex:1;min-width:280px;"
                      onsubmit="return confirm('Rifiutare la richiesta? L\'utente potrà correggere i dati e reinviarla.');">
                    @csrf
                    <div class="sg-flex align-items-start" style="gap:8px;">
                        <input name="reason" type="text" class="sg-form-control"
                               placeholder="Motivo del rifiuto (opzionale)" maxlength="500">
                        <button class="sg-btn sg-btn-outline">
                            <i class="fas fa-times"></i> Rifiuta
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

</div>
@endsection
