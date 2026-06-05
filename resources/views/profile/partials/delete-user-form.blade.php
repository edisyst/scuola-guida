<p class="sg-text-muted sg-mb-2">
    {{ __('profile.delete_account_desc') }}
</p>

<button type="button" class="sg-btn sg-btn-danger" data-toggle="modal" data-target="#confirmDeletionModal">
    <i class="fas fa-trash"></i> {{ __('profile.delete_account') }}
</button>

<div class="modal fade" id="confirmDeletionModal" tabindex="-1" role="dialog" aria-labelledby="confirmDeletionLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
        <form method="post" action="{{ route('profile.destroy') }}">
            @csrf
            @method('delete')
            <div class="modal-content sg-modal-content">
                <div class="modal-header sg-modal-header-dark">
                    <h5 class="modal-title" id="confirmDeletionLabel">
                        {{ __('profile.delete_account_confirm_title') }}
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="sg-text-muted sg-mb-2">
                        {{ __('profile.delete_account_confirm_desc') }}
                    </p>
                    <div class="sg-form-group">
                        <label for="delete_password" class="sg-form-label">{{ __('profile.current_password') }}</label>
                        <input id="delete_password" name="password" type="password"
                               class="sg-form-control @if($errors->userDeletion->has('password')) is-invalid @endif"
                               placeholder="{{ __('profile.current_password') }}">
                        @if ($errors->userDeletion->has('password'))
                            <div class="sg-form-error">{{ $errors->userDeletion->first('password') }}</div>
                        @endif
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="sg-btn sg-btn-light" data-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="submit" class="sg-btn sg-btn-danger">
                        <i class="fas fa-trash"></i> {{ __('profile.delete_account') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
