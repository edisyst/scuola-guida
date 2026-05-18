<div>
    @auth
        @if(auth()->user()->isViewer())
            <button wire:click="toggleBookmark"
                    wire:loading.attr="disabled"
                    class="btn btn-sm {{ $isBookmarked ? 'btn-warning' : 'btn-outline-secondary' }}">
                <i class="{{ $isBookmarked ? 'fas' : 'far' }} fa-bookmark"></i>
                <span wire:loading.remove>{{ $isBookmarked ? 'Salvato' : 'Salva' }}</span>
                <span wire:loading><i class="fas fa-spinner fa-spin"></i></span>
            </button>

            @if($isBookmarked)
                <div class="mt-1" x-data="{ open: false }">
                    <button type="button"
                            @click="open = !open"
                            class="btn btn-link btn-sm p-0 text-muted">
                        <span x-text="open ? 'Chiudi nota' : ($wire.note ? 'Modifica nota' : 'Aggiungi nota')"></span>
                    </button>
                    <div x-show="open" x-transition class="mt-1">
                        <textarea wire:model.defer="note"
                                  maxlength="500"
                                  rows="2"
                                  class="form-control form-control-sm"
                                  placeholder="Nota personale..."></textarea>
                        @error('note')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                        <button wire:click="saveNote"
                                wire:loading.attr="disabled"
                                class="btn btn-sm btn-primary mt-1">
                            Salva nota
                        </button>
                    </div>
                </div>
            @endif
        @endif
    @endauth
</div>
