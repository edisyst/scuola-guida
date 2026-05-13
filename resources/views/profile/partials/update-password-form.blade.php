<form method="post" action="{{ route('password.update') }}">
    @csrf
    @method('put')

    <div class="sg-form-group">
        <label for="update_password_current_password" class="sg-form-label">{{ __('Password attuale') }}</label>
        <input id="update_password_current_password" name="current_password" type="password"
               class="sg-form-control @if($errors->updatePassword->has('current_password')) is-invalid @endif"
               autocomplete="current-password">
        @if ($errors->updatePassword->has('current_password'))
            <div class="sg-form-error">{{ $errors->updatePassword->first('current_password') }}</div>
        @endif
    </div>

    <div class="sg-form-group">
        <label for="update_password_password" class="sg-form-label">{{ __('Nuova password') }}</label>
        <input id="update_password_password" name="password" type="password"
               class="sg-form-control @if($errors->updatePassword->has('password')) is-invalid @endif"
               autocomplete="new-password">
        @if ($errors->updatePassword->has('password'))
            <div class="sg-form-error">{{ $errors->updatePassword->first('password') }}</div>
        @endif
    </div>

    <div class="sg-form-group">
        <label for="update_password_password_confirmation" class="sg-form-label">{{ __('Conferma password') }}</label>
        <input id="update_password_password_confirmation" name="password_confirmation" type="password"
               class="sg-form-control @if($errors->updatePassword->has('password_confirmation')) is-invalid @endif"
               autocomplete="new-password">
        @if ($errors->updatePassword->has('password_confirmation'))
            <div class="sg-form-error">{{ $errors->updatePassword->first('password_confirmation') }}</div>
        @endif
    </div>

    <button type="submit" class="sg-btn sg-btn-primary sg-mt-2">
        <i class="fas fa-key"></i> {{ __('Aggiorna password') }}
    </button>
</form>
