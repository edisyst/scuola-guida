<div>
    @auth
        @if(auth()->user()->isViewer())
            <button type="button"
                    wire:click="toggleForm"
                    wire:loading.attr="disabled"
                    wire:target="toggleForm"
                    class="btn btn-sm btn-outline-warning"
                    title="{{ __('flags.tooltip_title') }}">
                <i class="fas fa-flag"></i>
                <span class="d-none d-md-inline ms-1">{{ __('flags.report_short') }}</span>
            </button>

            @if($submitted)
                <span class="text-success small ms-1">
                    <i class="fas fa-check"></i> {{ __('flags.report_sent') }}
                </span>
            @endif

            @if($open)
            <div class="mt-2 p-3 border rounded bg-light"
                 style="min-width: 280px;">
                <h6 class="text-warning mb-2">
                    <i class="fas fa-flag"></i> {{ __('flags.report_title') }}
                </h6>

                <div class="mb-2">
                    <label class="form-label form-label-sm mb-1">{{ __('flags.type_label') }}</label>
                    <select wire:model.blur="type" class="sg-form-control form-control form-control-sm">
                        @foreach(\App\Models\QuestionReport::types() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('type') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>

                <div class="mb-2">
                    <label class="form-label form-label-sm mb-1">{{ __('flags.body_label') }}</label>
                    <textarea wire:model.blur="body"
                              rows="3"
                              maxlength="1000"
                              class="form-control form-control-sm"
                              placeholder="{{ __('flags.body_placeholder') }}"></textarea>
                    @error('body') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>

                <div class="d-flex gap-2">
                    <button type="button"
                            wire:click="sendReport"
                            wire:loading.attr="disabled"
                            wire:target="sendReport"
                            class="btn btn-sm btn-warning">
                        <span wire:loading.remove wire:target="sendReport">{{ __('flags.send_btn') }}</span>
                        <span wire:loading wire:target="sendReport">
                            <i class="fas fa-spinner fa-spin"></i> {{ __('flags.sending') }}
                        </span>
                    </button>
                    <button type="button"
                            wire:click="toggleForm"
                            wire:loading.attr="disabled"
                            wire:target="toggleForm"
                            class="btn btn-sm btn-outline-secondary">
                        {{ __('common.cancel') }}
                    </button>
                </div>
            </div>
            @endif
        @endif
    @endauth
</div>
