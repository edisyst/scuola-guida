<p class="text-muted">
    {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.') }}
</p>

<button type="button" class="btn btn-danger" data-toggle="modal" data-target="#confirmDeletionModal">
    {{ __('Delete Account') }}
</button>

<div class="modal fade" id="confirmDeletionModal" tabindex="-1" role="dialog" aria-labelledby="confirmDeletionLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form method="post" action="{{ route('profile.destroy') }}">
            @csrf
            @method('delete')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmDeletionLabel">
                        {{ __('Are you sure you want to delete your account?') }}
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">
                        {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
                    </p>
                    <div class="form-group mb-0">
                        <label for="delete_password" class="sr-only">{{ __('Password') }}</label>
                        <input id="delete_password" name="password" type="password"
                               class="form-control @if($errors->userDeletion->has('password')) is-invalid @endif"
                               placeholder="{{ __('Password') }}">
                        @if ($errors->userDeletion->has('password'))
                            <div class="invalid-feedback">{{ $errors->userDeletion->first('password') }}</div>
                        @endif
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-danger">{{ __('Delete Account') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>
