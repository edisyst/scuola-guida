<div>
    @auth
        @if(auth()->user()->isViewer())
            <button type="button"
                    wire:click="toggleForm"
                    wire:loading.attr="disabled"
                    wire:target="toggleForm"
                    class="btn btn-sm btn-outline-warning"
                    title="Segnala un problema con questa domanda">
                <i class="fas fa-flag"></i>
                <span class="d-none d-md-inline ms-1">Segnala</span>
            </button>

            @if($submitted)
                <span class="text-success small ms-1">
                    <i class="fas fa-check"></i> Segnalazione inviata
                </span>
            @endif

            @if($open)
            <div class="mt-2 p-3 border rounded bg-light"
                 style="min-width: 280px;">
                <h6 class="text-warning mb-2">
                    <i class="fas fa-flag"></i> Segnala un problema
                </h6>

                <div class="mb-2">
                    <label class="form-label form-label-sm mb-1">Tipo di problema</label>
                    <select wire:model.blur="type" class="form-control form-control-sm">
                        @foreach(\App\Models\QuestionReport::types() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('type') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>

                <div class="mb-2">
                    <label class="form-label form-label-sm mb-1">Descrivi il problema (min 10 caratteri)</label>
                    <textarea wire:model.blur="body"
                              rows="3"
                              maxlength="1000"
                              class="form-control form-control-sm"
                              placeholder="Es: La risposta corretta indicata è VERO, ma secondo il Codice della Strada..."></textarea>
                    @error('body') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>

                <div class="d-flex gap-2">
                    <button type="button"
                            wire:click="sendReport"
                            wire:loading.attr="disabled"
                            wire:target="sendReport"
                            class="btn btn-sm btn-warning">
                        <span wire:loading.remove wire:target="sendReport">Invia segnalazione</span>
                        <span wire:loading wire:target="sendReport">
                            <i class="fas fa-spinner fa-spin"></i> Invio...
                        </span>
                    </button>
                    <button type="button"
                            wire:click="toggleForm"
                            wire:loading.attr="disabled"
                            wire:target="toggleForm"
                            class="btn btn-sm btn-outline-secondary">
                        Annulla
                    </button>
                </div>
            </div>
            @endif
        @endif
    @endauth
</div>
