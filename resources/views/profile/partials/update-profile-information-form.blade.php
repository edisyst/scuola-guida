<form id="send-verification" method="post" action="{{ route('verification.send') }}">
    @csrf
</form>

<form method="post" action="{{ route('profile.update') }}">
    @csrf
    @method('patch')

    <div class="sg-form-group">
        <label for="name" class="sg-form-label">{{ __('profile.name_label') }}</label>
        <input id="name" name="name" type="text"
               class="sg-form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $user->name) }}"
               required autofocus autocomplete="name">
        @error('name')
            <div class="sg-form-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="sg-form-group">
        <label for="email" class="sg-form-label">{{ __('profile.email_label') }}</label>
        <input id="email" name="email" type="email"
               class="sg-form-control @error('email') is-invalid @enderror"
               value="{{ old('email', $user->email) }}"
               required autocomplete="username">
        @error('email')
            <div class="sg-form-error">{{ $message }}</div>
        @enderror

        @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
            <div class="sg-mt-2">
                <p class="sg-form-hint sg-mb-1">
                    {{ __('profile.email_unverified') }}
                    <button form="send-verification" class="sg-btn-reset sg-link" style="font-size:.82rem;">
                        {{ __('profile.send_verification') }}
                    </button>
                </p>
                @if (session('status') === 'verification-link-sent')
                    <p class="sg-text-success sg-mb-0" style="font-size:.82rem;font-weight:600;">
                        {{ __('profile.verification_sent') }}
                    </p>
                @endif
            </div>
        @endif
    </div>

    <button type="submit" class="sg-btn sg-btn-primary sg-mt-2">
        <i class="fas fa-save"></i> {{ __('common.save') }}
    </button>
</form>
