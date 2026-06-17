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
        {!! __('profile.reg_approved') !!}
        @if($user->registration_reviewed_at)
            <small class="sg-text-muted d-block">
                {{ __('profile.reg_approved_on', ['date' => $user->registration_reviewed_at->format('d/m/Y H:i')]) }}
                @if($user->registrationReviewer) {{ __('profile.reg_approved_by', ['name' => $user->registrationReviewer->name]) }} @endif
            </small>
        @endif
        <small class="d-block sg-mt-1">
            <i class="fas fa-info-circle"></i>
            {!! __('profile.reg_resubmit_warn') !!}
        </small>
    </div>
@elseif($isPending)
    <div class="alert alert-warning sg-mb-3">
        <i class="fas fa-hourglass-half"></i>
        {!! __('profile.reg_pending') !!}
        @if($user->registration_submitted_at)
            <small class="sg-text-muted d-block">
                {{ __('profile.reg_pending_sent', ['date' => $user->registration_submitted_at->format('d/m/Y H:i')]) }}
            </small>
        @endif
        <small class="d-block sg-mt-1">
            {!! __('profile.reg_pending_practice') !!}
        </small>
    </div>
@elseif($isRejected)
    <div class="alert alert-danger sg-mb-3">
        <i class="fas fa-times-circle"></i>
        {!! __('profile.reg_rejected') !!}
        @if($user->registration_rejection_reason)
            <div class="sg-mt-1"><strong>{{ __('profile.reg_rejected_reason') }}</strong> {{ $user->registration_rejection_reason }}</div>
        @endif
        <small class="d-block sg-mt-1">
            {{ __('profile.reg_rejected_fix') }}
        </small>
    </div>
@else
    <div class="alert alert-info sg-mb-3">
        <i class="fas fa-info-circle"></i>
        {!! __('profile.reg_none') !!}
        {!! __('profile.reg_practice_meanwhile') !!}
    </div>
@endif

<form method="POST" action="{{ route('profile.registration.submit') }}" enctype="multipart/form-data">
    @csrf

    @if(array_key_exists('first_name', $enrollFields) || array_key_exists('last_name', $enrollFields))
    <div class="row">
        @if(array_key_exists('first_name', $enrollFields))
        <div class="col-12 {{ array_key_exists('last_name', $enrollFields) ? 'col-md-6' : '' }}">
            <div class="sg-form-group">
                <label for="first_name" class="sg-form-label">
                    {{ __('profile.field_first_name') }}
                    @if($enrollFields['first_name']['required']) *@endif
                </label>
                <input id="first_name" name="first_name" type="text"
                       class="sg-form-control @error('first_name') is-invalid @enderror"
                       value="{{ old('first_name', $user->first_name) }}"
                       @if($enrollFields['first_name']['required']) required @endif>
                @error('first_name')<div class="sg-form-error">{{ $message }}</div>@enderror
            </div>
        </div>
        @endif
        @if(array_key_exists('last_name', $enrollFields))
        <div class="col-12 {{ array_key_exists('first_name', $enrollFields) ? 'col-md-6' : '' }}">
            <div class="sg-form-group">
                <label for="last_name" class="sg-form-label">
                    {{ __('profile.field_last_name') }}
                    @if($enrollFields['last_name']['required']) *@endif
                </label>
                <input id="last_name" name="last_name" type="text"
                       class="sg-form-control @error('last_name') is-invalid @enderror"
                       value="{{ old('last_name', $user->last_name) }}"
                       @if($enrollFields['last_name']['required']) required @endif>
                @error('last_name')<div class="sg-form-error">{{ $message }}</div>@enderror
            </div>
        </div>
        @endif
    </div>
    @endif

    @if(array_key_exists('address', $enrollFields))
    <div class="sg-form-group">
        <label for="address" class="sg-form-label">
            {{ __('profile.field_address') }}
            @if($enrollFields['address']['required']) *@endif
        </label>
        <input id="address" name="address" type="text"
               class="sg-form-control @error('address') is-invalid @enderror"
               value="{{ old('address', $user->address) }}"
               @if($enrollFields['address']['required']) required @endif
               placeholder="{{ __('profile.field_address_ph') }}">
        @error('address')<div class="sg-form-error">{{ $message }}</div>@enderror
    </div>
    @endif

    @if(array_key_exists('birth_date', $enrollFields) || array_key_exists('birth_place', $enrollFields))
    <div class="row">
        @if(array_key_exists('birth_date', $enrollFields))
        <div class="col-12 {{ array_key_exists('birth_place', $enrollFields) ? 'col-md-6' : '' }}">
            <div class="sg-form-group">
                <label for="birth_date" class="sg-form-label">
                    {{ __('profile.field_birth_date') }}
                    @if($enrollFields['birth_date']['required']) *@endif
                </label>
                <input id="birth_date" name="birth_date" type="date"
                       class="sg-form-control @error('birth_date') is-invalid @enderror"
                       value="{{ old('birth_date', optional($user->birth_date)->format('Y-m-d')) }}"
                       @if($enrollFields['birth_date']['required']) required @endif>
                @error('birth_date')<div class="sg-form-error">{{ $message }}</div>@enderror
            </div>
        </div>
        @endif
        @if(array_key_exists('birth_place', $enrollFields))
        <div class="col-12 {{ array_key_exists('birth_date', $enrollFields) ? 'col-md-6' : '' }}">
            <div class="sg-form-group">
                <label for="birth_place" class="sg-form-label">
                    {{ __('profile.field_birth_place') }}
                    @if($enrollFields['birth_place']['required']) *@endif
                </label>
                <input id="birth_place" name="birth_place" type="text"
                       class="sg-form-control @error('birth_place') is-invalid @enderror"
                       value="{{ old('birth_place', $user->birth_place) }}"
                       @if($enrollFields['birth_place']['required']) required @endif>
                @error('birth_place')<div class="sg-form-error">{{ $message }}</div>@enderror
            </div>
        </div>
        @endif
    </div>
    @endif

    @if(array_key_exists('fiscal_code', $enrollFields))
    <div class="sg-form-group">
        <label for="fiscal_code" class="sg-form-label">
            {{ __('profile.field_fiscal_code') }}
            @if($enrollFields['fiscal_code']['required']) *@endif
        </label>
        <input id="fiscal_code" name="fiscal_code" type="text"
               class="sg-form-control text-uppercase @error('fiscal_code') is-invalid @enderror"
               value="{{ old('fiscal_code', $user->fiscal_code) }}"
               @if($enrollFields['fiscal_code']['required']) required @endif
               maxlength="16">
        @error('fiscal_code')<div class="sg-form-error">{{ $message }}</div>@enderror
    </div>
    @endif

    @if(array_key_exists('id_document', $enrollFields))
    <div class="sg-form-group">
        <label for="id_document" class="sg-form-label">
            {{ __('profile.field_document') }}
            @if(!$hasFile && $enrollFields['id_document']['required']) *@endif
        </label>
        @if($hasFile)
            <div class="sg-mb-1">
                <span class="sg-badge">
                    <i class="fas fa-paperclip"></i> {{ __('profile.document_uploaded') }}
                </span>
                <small class="sg-text-muted">— {{ __('profile.document_replace') }}</small>
            </div>
        @endif
        <input id="id_document" name="id_document" type="file"
               class="sg-form-control @error('id_document') is-invalid @enderror"
               accept=".pdf,.jpg,.jpeg,.png"
               @if(!$hasFile && $enrollFields['id_document']['required']) required @endif>
        <small class="sg-text-muted">{{ __('profile.document_formats') }}</small>
        @error('id_document')<div class="sg-form-error">{{ $message }}</div>@enderror
    </div>
    @endif

    <button type="submit" class="sg-btn sg-btn-primary sg-mt-2"
            @if(!$isApproved && !$isRejected && !$isPending) onclick="return confirm('{{ __('profile.confirm_first_send') }}');"
            @elseif($isApproved) onclick="return confirm('{{ __('profile.confirm_reapprove') }}');"
            @endif>
        <i class="fas fa-paper-plane"></i>
        @if($isApproved)
            {{ __('profile.submit_reapprove') }}
        @elseif($isPending)
            {{ __('profile.submit_update_pending') }}
        @elseif($isRejected)
            {{ __('profile.submit_rejected') }}
        @else
            {{ __('profile.submit_first') }}
        @endif
    </button>
</form>
