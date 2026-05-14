<p class="sg-text-muted sg-mb-2">
    {{ __('Una volta eliminato l\'account, tutte le risorse e i dati saranno cancellati definitivamente. Prima di procedere, scarica eventuali dati che desideri conservare.') }}
</p>

<button type="button" class="sg-btn sg-btn-danger" data-toggle="modal" data-target="#confirmDeletionModal">
    <i class="fas fa-trash"></i> {{ __('Elimina account') }}
</button>

<div class="modal fade" id="confirmDeletionModal" tabindex="-1" role="dialog" aria-labelledby="confirmDeletionLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form method="post" action="{{ route('profile.destroy') }}">
            @csrf
            @method('delete')
            <div class="modal-content" style="border:none;border-radius:var(--sg-radius);box-shadow:var(--sg-shadow-card);overflow:hidden;">
                <div class="modal-header" style="background:var(--sg-gradient-dark);color:#fff;border:none;">
                    <h5 class="modal-title" id="confirmDeletionLabel">
                        {{ __('Vuoi davvero eliminare il tuo account?') }}
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color:#fff;opacity:.8;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="sg-text-muted sg-mb-2">
                        {{ __('Una volta eliminato l\'account, tutte le risorse e i dati saranno cancellati definitivamente. Inserisci la password per confermare l\'eliminazione.') }}
                    </p>
                    <div class="sg-form-group">
                        <label for="delete_password" class="sg-form-label">{{ __('Password') }}</label>
                        <input id="delete_password" name="password" type="password"
                               class="sg-form-control @if($errors->userDeletion->has('password')) is-invalid @endif"
                               placeholder="{{ __('Password') }}">
                        @if ($errors->userDeletion->has('password'))
                            <div class="sg-form-error">{{ $errors->userDeletion->first('password') }}</div>
                        @endif
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--sg-border-light);">
                    <button type="button" class="sg-btn sg-btn-light" data-dismiss="modal">{{ __('Annulla') }}</button>
                    <button type="submit" class="sg-btn sg-btn-danger">
                        <i class="fas fa-trash"></i> {{ __('Elimina account') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
