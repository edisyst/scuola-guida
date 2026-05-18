@php
    /** @var \App\Models\User $user */
    $isApproved = $user->isRegistrationApproved();
    $isPending  = $user->isRegistrationPending();
    $isRejected = $user->isRegistrationRejected();
    $hasFile    = !empty($user->id_document_path);
@endphp

@if($isApproved)
    <div class="alert alert-success sg-mb-3">
        <i class="fas fa-check-circle"></i>
        Sei abilitato a iscriverti agli <strong>esami ufficiali</strong> per la patente.
        @if($user->registration_reviewed_at)
            <small class="sg-text-muted d-block">
                Approvata il {{ $user->registration_reviewed_at->format('d/m/Y H:i') }}
                @if($user->registrationReviewer) da {{ $user->registrationReviewer->name }} @endif
            </small>
        @endif
        <small class="d-block sg-mt-1">
            <i class="fas fa-info-circle"></i>
            Se modifichi i dati anagrafici e li reinvii, dovrai essere nuovamente abilitato
            dall'amministratore prima di poter partecipare a nuovi esami.
        </small>
    </div>
@elseif($isPending)
    <div class="alert alert-warning sg-mb-3">
        <i class="fas fa-hourglass-half"></i>
        La tua richiesta è <strong>in attesa di approvazione</strong>.
        @if($user->registration_submitted_at)
            <small class="sg-text-muted d-block">
                Inviata il {{ $user->registration_submitted_at->format('d/m/Y H:i') }}
            </small>
        @endif
        <small class="d-block sg-mt-1">
            Puoi comunque <strong>esercitarti liberamente con i quiz</strong> in attesa della revisione.
        </small>
    </div>
@elseif($isRejected)
    <div class="alert alert-danger sg-mb-3">
        <i class="fas fa-times-circle"></i>
        La tua richiesta è stata <strong>rifiutata</strong>.
        @if($user->registration_rejection_reason)
            <div class="sg-mt-1"><strong>Motivo:</strong> {{ $user->registration_rejection_reason }}</div>
        @endif
        <small class="d-block sg-mt-1">
            Correggi i dati e invia nuovamente la richiesta.
        </small>
    </div>
@else
    <div class="alert alert-info sg-mb-3">
        <i class="fas fa-info-circle"></i>
        Per iscriverti agli <strong>esami ufficiali</strong> della patente devi prima inviare i tuoi dati anagrafici
        e attendere l'approvazione dell'amministratore.
        Nel frattempo puoi sempre <strong>esercitarti con i quiz</strong> a piacere.
    </div>
@endif

<form method="POST" action="{{ route('profile.registration.submit') }}" enctype="multipart/form-data">
    @csrf

    <div class="row">
        <div class="col-12 col-md-6">
            <div class="sg-form-group">
                <label for="first_name" class="sg-form-label">Nome *</label>
                <input id="first_name" name="first_name" type="text"
                       class="sg-form-control @error('first_name') is-invalid @enderror"
                       value="{{ old('first_name', $user->first_name) }}" required>
                @error('first_name')<div class="sg-form-error">{{ $message }}</div>@enderror
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="sg-form-group">
                <label for="last_name" class="sg-form-label">Cognome *</label>
                <input id="last_name" name="last_name" type="text"
                       class="sg-form-control @error('last_name') is-invalid @enderror"
                       value="{{ old('last_name', $user->last_name) }}" required>
                @error('last_name')<div class="sg-form-error">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>

    <div class="sg-form-group">
        <label for="address" class="sg-form-label">Indirizzo di residenza *</label>
        <input id="address" name="address" type="text"
               class="sg-form-control @error('address') is-invalid @enderror"
               value="{{ old('address', $user->address) }}" required
               placeholder="Via, numero civico, città, CAP">
        @error('address')<div class="sg-form-error">{{ $message }}</div>@enderror
    </div>

    <div class="row">
        <div class="col-12 col-md-6">
            <div class="sg-form-group">
                <label for="birth_date" class="sg-form-label">Data di nascita *</label>
                <input id="birth_date" name="birth_date" type="date"
                       class="sg-form-control @error('birth_date') is-invalid @enderror"
                       value="{{ old('birth_date', optional($user->birth_date)->format('Y-m-d')) }}" required>
                @error('birth_date')<div class="sg-form-error">{{ $message }}</div>@enderror
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="sg-form-group">
                <label for="birth_place" class="sg-form-label">Luogo di nascita *</label>
                <input id="birth_place" name="birth_place" type="text"
                       class="sg-form-control @error('birth_place') is-invalid @enderror"
                       value="{{ old('birth_place', $user->birth_place) }}" required>
                @error('birth_place')<div class="sg-form-error">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>

    <div class="sg-form-group">
        <label for="fiscal_code" class="sg-form-label">Codice fiscale *</label>
        <input id="fiscal_code" name="fiscal_code" type="text"
               class="sg-form-control text-uppercase @error('fiscal_code') is-invalid @enderror"
               value="{{ old('fiscal_code', $user->fiscal_code) }}" required
               maxlength="16">
        @error('fiscal_code')<div class="sg-form-error">{{ $message }}</div>@enderror
    </div>

    <div class="sg-form-group">
        <label for="id_document" class="sg-form-label">
            Documento di identità @if(!$hasFile)*@endif
        </label>
        @if($hasFile)
            <div class="sg-mb-1">
                <span class="sg-badge">
                    <i class="fas fa-paperclip"></i> Documento caricato
                </span>
                <small class="sg-text-muted">— carica un nuovo file solo se vuoi sostituirlo</small>
            </div>
        @endif
        <input id="id_document" name="id_document" type="file"
               class="sg-form-control @error('id_document') is-invalid @enderror"
               accept=".pdf,.jpg,.jpeg,.png" @if(!$hasFile) required @endif>
        <small class="sg-text-muted">Formati ammessi: PDF, JPG, PNG. Dimensione massima 5 MB.</small>
        @error('id_document')<div class="sg-form-error">{{ $message }}</div>@enderror
    </div>

    <button type="submit" class="sg-btn sg-btn-primary sg-mt-2"
            @if(!$isApproved && !$isRejected && !$isPending) onclick="return confirm('Confermi l\'invio dei dati per l\'iscrizione agli esami ufficiali?');"
            @elseif($isApproved) onclick="return confirm('Reinviando i dati perderai temporaneamente l\'abilitazione agli esami fino alla riapprovazione dell\'amministratore. Procedere?');"
            @endif>
        <i class="fas fa-paper-plane"></i>
        @if($isApproved)
            Reinvia dati (richiede nuova approvazione)
        @elseif($isPending)
            Aggiorna richiesta in attesa
        @elseif($isRejected)
            Reinvia richiesta
        @else
            Invia richiesta di iscrizione
        @endif
    </button>
</form>
