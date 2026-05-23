@if($user->hasTwoFactorEnabled())

    {{-- 2FA ABILITATO --}}
    <p class="sg-text-muted sg-mb-2">
        <i class="fas fa-check-circle text-success mr-1"></i>
        2FA attivo dal <strong>{{ $user->two_factor_enabled_at->format('d/m/Y') }}</strong>.
    </p>

    {{-- Disabilita 2FA --}}
    <button type="button" class="sg-btn sg-btn-warning sg-mb-1" data-toggle="modal" data-target="#disableTwoFactorModal">
        <i class="fas fa-lock-open mr-1"></i> Disabilita 2FA
    </button>

    {{-- Rigenera codici --}}
    <button type="button" class="sg-btn sg-btn-secondary sg-mb-1" data-toggle="modal" data-target="#regenerateCodesModal">
        <i class="fas fa-redo mr-1"></i> Rigenera codici di recupero
    </button>

    {{-- Modal: disabilita --}}
    <div class="modal fade" id="disableTwoFactorModal" tabindex="-1" role="dialog" aria-labelledby="disableTwoFactorLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <form method="POST" action="{{ route('2fa.disable') }}">
                @csrf
                <div class="modal-content sg-modal-content">
                    <div class="modal-header sg-modal-header-dark">
                        <h5 class="modal-title" id="disableTwoFactorLabel">Disabilita autenticazione a due fattori</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p class="sg-text-muted sg-mb-2">
                            Inserisci la password corrente per confermare la disabilitazione del 2FA.
                        </p>
                        <div class="sg-form-group">
                            <label for="disable_2fa_password" class="sg-form-label">Password</label>
                            <input id="disable_2fa_password" name="password" type="password"
                                   class="sg-form-control @if($errors->twoFactorDisable->has('password')) is-invalid @endif"
                                   placeholder="Password">
                            @if($errors->twoFactorDisable->has('password'))
                                <div class="sg-form-error">{{ $errors->twoFactorDisable->first('password') }}</div>
                            @endif
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="sg-btn sg-btn-light" data-dismiss="modal">Annulla</button>
                        <button type="submit" class="sg-btn sg-btn-warning">
                            <i class="fas fa-lock-open mr-1"></i> Disabilita 2FA
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal: rigenera codici --}}
    <div class="modal fade" id="regenerateCodesModal" tabindex="-1" role="dialog" aria-labelledby="regenerateCodesLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <form method="POST" action="{{ route('2fa.codes.regenerate') }}">
                @csrf
                <div class="modal-content sg-modal-content">
                    <div class="modal-header sg-modal-header-dark">
                        <h5 class="modal-title" id="regenerateCodesLabel">Rigenera codici di recupero</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p class="sg-text-muted sg-mb-2">
                            I codici attuali verranno invalidati. Inserisci la password per procedere.
                        </p>
                        <div class="sg-form-group">
                            <label for="regen_password" class="sg-form-label">Password</label>
                            <input id="regen_password" name="password" type="password"
                                   class="sg-form-control @if($errors->twoFactorRegenerate->has('password')) is-invalid @endif"
                                   placeholder="Password">
                            @if($errors->twoFactorRegenerate->has('password'))
                                <div class="sg-form-error">{{ $errors->twoFactorRegenerate->first('password') }}</div>
                            @endif
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="sg-btn sg-btn-light" data-dismiss="modal">Annulla</button>
                        <button type="submit" class="sg-btn sg-btn-secondary">
                            <i class="fas fa-redo mr-1"></i> Rigenera codici
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

@else

    {{-- 2FA NON ABILITATO --}}
    <p class="sg-text-muted sg-mb-2">
        <i class="fas fa-exclamation-triangle text-warning mr-1"></i>
        Il 2FA non è ancora abilitato sul tuo account.
        È obbligatorio per accedere all'area admin.
    </p>

    <a href="{{ route('2fa.setup.show') }}" class="sg-btn sg-btn-primary">
        <i class="fas fa-shield-alt mr-1"></i> Abilita 2FA
    </a>

@endif
