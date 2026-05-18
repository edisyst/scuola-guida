<div>
    {{-- Flash message --}}
    @if (session()->has('media_success'))
        <div class="alert alert-success alert-dismissible fade show sg-mb-4" role="alert">
            <i class="fas fa-check-circle mr-1"></i> {{ session('media_success') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    {{-- TAB CARTELLE --}}
    <div class="sg-card sg-mb-4 p-4">
        <div class="d-flex align-items-center flex-wrap" style="gap:1rem;">
            <span class="text-muted">
                <i class="fas fa-folder-open text-warning mr-2"></i> Cartella:
            </span>
            @foreach ($folders as $key => $path)
                <button type="button"
                        wire:click="switchFolder('{{ $key }}')"
                        class="btn btn-sm {{ $folder === $key ? 'btn-primary' : 'btn-outline-secondary' }}">
                    {{ ucfirst($key) }}
                    <span class="badge {{ $folder === $key ? 'badge-light' : 'badge-secondary' }} ml-2">
                        {{ $folderCounts[$key] ?? 0 }}
                    </span>
                </button>
            @endforeach
            <code class="ml-auto text-muted d-none d-md-inline" style="font-size:0.9rem;">{{ $disk }} &rarr; {{ $directory }}</code>
        </div>
    </div>

    {{-- UPLOAD --}}
    <div class="sg-card sg-mb-5 p-4">
        <h5 class="sg-section-title sg-mb-3">
            <i class="fas fa-upload"></i>
            Carica nuova immagine in <strong>{{ $folder }}</strong>
        </h5>
        <div class="d-flex align-items-start flex-wrap" style="gap:1rem;">
            <div class="sg-upload-box">
                <input type="file" wire:model="newImage" accept="image/*" class="form-control">
                @error('newImage')
                    <div class="text-danger mt-2" style="font-size:0.95rem;">{{ $message }}</div>
                @enderror
                <div wire:loading wire:target="newImage" class="text-muted mt-2">
                    <i class="fas fa-spinner fa-spin"></i> Caricamento in corso...
                </div>
            </div>
            <button type="button" wire:click="save"
                    class="sg-btn sg-btn-primary sg-btn-sm"
                    wire:loading.attr="disabled" wire:target="save,newImage">
                <span wire:loading.remove wire:target="save">
                    <i class="fas fa-upload mr-1"></i> Carica
                </span>
                <span wire:loading wire:target="save">
                    <i class="fas fa-spinner fa-spin"></i> Caricamento...
                </span>
            </button>
        </div>
    </div>

    {{-- GRIGLIA FILE --}}
    @if (count($files) === 0)
        <div class="sg-card text-center text-muted py-5">
            <i class="fas fa-images fa-2x mb-2 d-block"></i>
            Nessuna immagine nella cartella <strong>{{ $folder }}</strong>.
        </div>
    @else
        <div class="row" style="row-gap:1.5rem;">
            @foreach ($files as $file)
                <div class="col-6 col-sm-4 col-md-3 col-lg-2" wire:key="{{ $file['path'] }}">
                    <div class="sg-card h-100 p-3 d-flex flex-column" style="gap:0.75rem;">

                        {{-- ANTEPRIMA --}}
                        <div class="sg-img-preview">
                            <img src="{{ $file['url'] }}" alt="{{ $file['name'] }}">
                        </div>

                        {{-- NOME / RENAME INLINE --}}
                        @if ($renamingFile === $file['path'])
                            <div>
                                <input type="text"
                                       wire:model.defer="newName"
                                       class="form-control form-control-sm @error('newName') is-invalid @enderror"
                                       wire:keydown.enter="rename"
                                       wire:keydown.escape="cancelRename"
                                       autofocus>
                                @error('newName')
                                    <div class="text-danger mt-2" style="font-size:0.85rem;">{{ $message }}</div>
                                @enderror
                                <div class="d-flex mt-2" style="gap:0.5rem;">
                                    <button wire:click="rename" class="btn btn-sm btn-success flex-fill" title="Conferma">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button wire:click="cancelRename" class="btn btn-sm btn-secondary flex-fill" title="Annulla">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        @else
                            <div class="text-center sg-media-filename" title="{{ $file['name'] }}">
                                <div class="font-weight-medium text-truncate">{{ $file['name'] }}</div>
                                <div class="text-muted" style="font-size:0.85rem;">
                                    {{ $file['size'] }}
                                    @if ($file['refs'] > 0)
                                        &middot;
                                        <span class="badge badge-info" title="Domande che usano questa immagine">
                                            {{ $file['refs'] }} {{ $file['refs'] === 1 ? 'ref' : 'refs' }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            {{-- AZIONI --}}
                            <div class="d-flex mt-auto" style="gap:0.5rem;">
                                <button wire:click="startRename('{{ $file['path'] }}')"
                                        class="btn btn-sm btn-outline-secondary flex-fill" title="Rinomina">
                                    <i class="fas fa-pen"></i>
                                </button>
                                <button wire:click="confirmDelete('{{ $file['path'] }}')"
                                        class="btn btn-sm btn-outline-danger flex-fill" title="Elimina">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
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
