<div>
    @auth
        @if(auth()->user()->isViewer())
            <button wire:click="toggleBookmark"
                    wire:loading.attr="disabled"
                    wire:target="toggleBookmark"
                    class="btn btn-sm {{ $isBookmarked ? 'btn-warning' : 'btn-outline-secondary' }}">
                <span wire:loading.remove wire:target="toggleBookmark">
                    <i class="{{ $isBookmarked ? 'fas' : 'far' }} fa-bookmark"></i>
                    {{ $isBookmarked ? 'Salvato' : 'Salva' }}
                </span>
                <span wire:loading wire:target="toggleBookmark">
                    <i class="fas fa-spinner fa-spin"></i>
                </span>
            </button>

            @if($isBookmarked)
                <div class="mt-1" x-data="{ open: false }">
                    <button type="button"
                            @click="open = !open"
                            class="btn btn-link btn-sm p-0 text-muted">
                        <i class="fas fa-sticky-note me-1"></i>
                        <span x-text="open ? 'Chiudi' : ($wire.note ? 'Modifica nota' : 'Aggiungi nota')"></span>
                    </button>
                    <div x-show="open" x-transition class="mt-1">
                        <textarea wire:model.blur="noteInput"
                                  maxlength="500"
                                  rows="2"
                                  class="form-control form-control-sm"
                                  placeholder="Nota personale... (max 500 caratteri)"></textarea>
                        @error('noteInput')
                            <small class="text-danger d-block">{{ $message }}</small>
                        @enderror
                        <button wire:click="saveNote"
                                wire:loading.attr="disabled"
                                wire:target="saveNote"
                                class="btn btn-sm btn-primary mt-1">
                            <span wire:loading.remove wire:target="saveNote">Salva nota</span>
                            <span wire:loading wire:target="saveNote">
                                <i class="fas fa-spinner fa-spin"></i>
                            </span>
                        </button>
                    </div>
                    @if($note)
                        <p class="text-muted small mt-1 mb-0 fst-italic" x-show="!open">
                            "{{ \Illuminate\Support\Str::limit($note, 60) }}"
                        </p>
                    @endif
                </div>
            @endif
        @endif
    @endauth
</div>
