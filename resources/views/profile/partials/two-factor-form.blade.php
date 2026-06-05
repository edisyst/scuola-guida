@if(config('two_factor.enabled'))
@if($user->hasTwoFactorEnabled())

    {{-- 2FA ABILITATO --}}
    <p class="sg-text-muted sg-mb-2">
        <i class="fas fa-check-circle text-success mr-1"></i>
        {{ __('profile.twofa_active_since', ['date' => $user->two_factor_enabled_at->format('d/m/Y')]) }}
    </p>

    {{-- Disabilita 2FA --}}
    <button type="button" class="sg-btn sg-btn-warning sg-mb-1" data-toggle="modal" data-target="#disableTwoFactorModal">
        <i class="fas fa-lock-open mr-1"></i> {{ __('profile.twofa_disable_btn') }}
    </button>

    {{-- Rigenera codici --}}
    <button type="button" class="sg-btn sg-btn-secondary sg-mb-1" data-toggle="modal" data-target="#regenerateCodesModal">
        <i class="fas fa-redo mr-1"></i> {{ __('profile.twofa_regenerate_btn') }}
    </button>

    {{-- Modal: disabilita --}}
    <div class="modal fade" id="disableTwoFactorModal" tabindex="-1" role="dialog" aria-labelledby="disableTwoFactorLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <form method="POST" action="{{ route('2fa.disable') }}">
                @csrf
                <div class="modal-content sg-modal-content">
                    <div class="modal-header sg-modal-header-dark">
                        <h5 class="modal-title" id="disableTwoFactorLabel">{{ __('profile.twofa_disable_title') }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p class="sg-text-muted sg-mb-2">
                            {{ __('profile.twofa_disable_desc') }}
                        </p>
                        <div class="sg-form-group">
                            <label for="disable_2fa_password" class="sg-form-label">{{ __('profile.current_password') }}</label>
                            <input id="disable_2fa_password" name="password" type="password"
                                   class="sg-form-control @if($errors->twoFactorDisable->has('password')) is-invalid @endif"
                                   placeholder="{{ __('profile.current_password') }}">
                            @if($errors->twoFactorDisable->has('password'))
                                <div class="sg-form-error">{{ $errors->twoFactorDisable->first('password') }}</div>
                            @endif
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="sg-btn sg-btn-light" data-dismiss="modal">{{ __('common.cancel') }}</button>
                        <button type="submit" class="sg-btn sg-btn-warning">
                            <i class="fas fa-lock-open mr-1"></i> {{ __('profile.twofa_disable_btn') }}
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
                        <h5 class="modal-title" id="regenerateCodesLabel">{{ __('profile.twofa_regen_title') }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p class="sg-text-muted sg-mb-2">
                            {{ __('profile.twofa_regen_desc') }}
                        </p>
                        <div class="sg-form-group">
                            <label for="regen_password" class="sg-form-label">{{ __('profile.current_password') }}</label>
                            <input id="regen_password" name="password" type="password"
                                   class="sg-form-control @if($errors->twoFactorRegenerate->has('password')) is-invalid @endif"
                                   placeholder="{{ __('profile.current_password') }}">
                            @if($errors->twoFactorRegenerate->has('password'))
                                <div class="sg-form-error">{{ $errors->twoFactorRegenerate->first('password') }}</div>
                            @endif
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="sg-btn sg-btn-light" data-dismiss="modal">{{ __('common.cancel') }}</button>
                        <button type="submit" class="sg-btn sg-btn-secondary">
                            <i class="fas fa-redo mr-1"></i> {{ __('profile.twofa_regen_btn') }}
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
        {{ __('profile.twofa_not_enabled') }}
    </p>

    <a href="{{ route('2fa.setup.show') }}" class="sg-btn sg-btn-primary">
        <i class="fas fa-shield-alt mr-1"></i> {{ __('profile.twofa_enable_btn') }}
    </a>

@endif
@else
    <p class="sg-text-muted">
        <i class="fas fa-info-circle mr-1"></i>
        {{ __('profile.twofa_platform_disabled') }}
    </p>
@endif
