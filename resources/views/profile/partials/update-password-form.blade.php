<form method="post" action="{{ route('password.update') }}">
    @csrf
    @method('put')

    <div class="form-group">
        <label for="update_password_current_password">{{ __('Current Password') }}</label>
        <input id="update_password_current_password" name="current_password" type="password"
               class="form-control @if($errors->updatePassword->has('current_password')) is-invalid @endif"
               autocomplete="current-password">
        @if ($errors->updatePassword->has('current_password'))
            <div class="invalid-feedback">{{ $errors->updatePassword->first('current_password') }}</div>
        @endif
    </div>

    <div class="form-group">
        <label for="update_password_password">{{ __('New Password') }}</label>
        <input id="update_password_password" name="password" type="password"
               class="form-control @if($errors->updatePassword->has('password')) is-invalid @endif"
               autocomplete="new-password">
        @if ($errors->updatePassword->has('password'))
            <div class="invalid-feedback">{{ $errors->updatePassword->first('password') }}</div>
        @endif
    </div>

    <div class="form-group">
        <label for="update_password_password_confirmation">{{ __('Confirm Password') }}</label>
        <input id="update_password_password_confirmation" name="password_confirmation" type="password"
               class="form-control @if($errors->updatePassword->has('password_confirmation')) is-invalid @endif"
               autocomplete="new-password">
        @if ($errors->updatePassword->has('password_confirmation'))
            <div class="invalid-feedback">{{ $errors->updatePassword->first('password_confirmation') }}</div>
        @endif
    </div>

    <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
</form>
