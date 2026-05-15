<div>
    {{-- Flash message --}}
    @if (session()->has('media_success'))
        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
            <i class="fas fa-check-circle mr-1"></i> {{ session('media_success') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    {{-- INFO CARTELLA --}}
    <div class="sg-card sg-mb-3">
        <div class="d-flex align-items-center gap-2">
            <i class="fas fa-folder-open text-warning mr-2"></i>
            <span class="text-muted small">Cartella attiva:</span>
            <code class="ml-1">{{ $disk }} &rarr; {{ $directory }}</code>
            <span class="badge badge-secondary ml-2">{{ count($files) }} file</span>
        </div>
    </div>

    {{-- UPLOAD --}}
    <div class="sg-card sg-mb-4">
        <h6 class="sg-section-title mb-3"><i class="fas fa-upload mr-1"></i> Carica nuova immagine</h6>
        <form wire:submit.prevent="upload">
            <div class="d-flex align-items-start gap-3" style="flex-wrap:wrap; gap:.75rem;">
                <div style="flex:1; min-width:260px;">
                    <input type="file" wire:model="newImage" accept="image/*" class="form-control-file">
                    @error('newImage')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                    <div wire:loading wire:target="newImage" class="text-muted small mt-1">
                        <i class="fas fa-spinner fa-spin"></i> Caricamento...
                    </div>
                </div>
                <button type="submit" class="sg-btn sg-btn-primary sg-btn-sm"
                        wire:loading.attr="disabled" wire:target="upload">
                    <span wire:loading.remove wire:target="upload">
                        <i class="fas fa-upload mr-1"></i> Carica
                    </span>
                    <span wire:loading wire:target="upload">
                        <i class="fas fa-spinner fa-spin"></i> Caricamento...
                    </span>
                </button>
            </div>
        </form>
    </div>

    {{-- GRIGLIA FILE --}}
    @if (count($files) === 0)
        <div class="sg-card text-center text-muted py-4">
            <i class="fas fa-images fa-2x mb-2 d-block"></i>
            Nessuna immagine nella cartella.
        </div>
    @else
        <div class="sg-card">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th style="width:80px"></th>
                            <th>Nome file</th>
                            <th>Dimensione</th>
                            <th>Domande</th>
                            <th class="text-right">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($files as $file)
                        <tr wire:key="{{ $file['path'] }}">
                            {{-- ANTEPRIMA --}}
                            <td class="align-middle">
                                <img src="{{ $file['url'] }}" alt="{{ $file['name'] }}"
                                     style="width:60px; height:45px; object-fit:cover; border-radius:4px; border:1px solid #dee2e6;">
                            </td>

                            {{-- NOME / RENAME INLINE --}}
                            <td class="align-middle">
                                @if ($renamingFile === $file['path'])
                                    <div class="d-flex align-items-center" style="gap:.4rem;">
                                        <input type="text"
                                               wire:model.defer="newName"
                                               class="form-control form-control-sm @error('newName') is-invalid @enderror"
                                               style="max-width:200px;"
                                               wire:keydown.enter="rename"
                                               wire:keydown.escape="cancelRename"
                                               autofocus>
                                        <button wire:click="rename" class="btn btn-sm btn-success" title="Conferma">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button wire:click="cancelRename" class="btn btn-sm btn-secondary" title="Annulla">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    @error('newName')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                @else
                                    <span class="font-weight-medium">{{ $file['name'] }}</span>
                                @endif
                            </td>

                            {{-- DIMENSIONE --}}
                            <td class="align-middle text-muted small">{{ $file['size'] }}</td>

                            {{-- RIFERIMENTI --}}
                            <td class="align-middle">
                                @if ($file['refs'] > 0)
                                    <span class="badge badge-info" title="Domande che usano questa immagine">
                                        {{ $file['refs'] }}
                                    </span>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>

                            {{-- AZIONI --}}
                            <td class="align-middle text-right">
                                @if ($renamingFile !== $file['path'])
                                    <button wire:click="startRename('{{ $file['path'] }}')"
                                            class="btn btn-sm btn-outline-secondary mr-1" title="Rinomina">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                    <button wire:click="confirmDelete('{{ $file['path'] }}')"
                                            class="btn btn-sm btn-outline-danger" title="Elimina">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- MODALE CONFERMA ELIMINAZIONE --}}
    @if ($deletingFile)
        <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-exclamation-triangle text-danger mr-1"></i> Conferma eliminazione
                        </h5>
                        <button type="button" class="close" wire:click="cancelDelete">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>Stai per eliminare <strong>{{ basename($deletingFile) }}</strong>.</p>
                        @if ($deletingRefs > 0)
                            <div class="alert alert-warning mb-2">
                                <i class="fas fa-exclamation-circle mr-1"></i>
                                Questo file è usato da <strong>{{ $deletingRefs }}</strong>
                                {{ $deletingRefs === 1 ? 'domanda' : 'domande' }}.
                                Il campo immagine verrà azzerato su quelle domande.
                            </div>
                        @endif
                        <p class="mb-0 text-muted small">Questa azione non può essere annullata.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="sg-btn sg-btn-secondary sg-btn-sm"
                                wire:click="cancelDelete">Annulla</button>
                        <button type="button" class="sg-btn sg-btn-danger sg-btn-sm"
                                wire:click="delete"
                                wire:loading.attr="disabled" wire:target="delete">
                            <span wire:loading.remove wire:target="delete">
                                <i class="fas fa-trash mr-1"></i> Elimina
                            </span>
                            <span wire:loading wire:target="delete">
                                <i class="fas fa-spinner fa-spin"></i> Eliminando...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
