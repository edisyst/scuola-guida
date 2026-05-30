<div>

    {{-- Card collassabile storico versioni --}}
    <div class="card mt-4" id="card-version-history">
        <div class="card-header" style="cursor:pointer;"
             data-toggle="collapse" data-target="#collapse-version-history"
             aria-expanded="false">
            <h3 class="card-title mb-0">
                <i class="fas fa-history mr-2"></i>
                Storico versioni
                <span class="badge badge-secondary ml-1">{{ $versionsWithDiff->count() }}</span>
            </h3>
            <div class="card-tools">
                <button type="button" class="btn btn-tool">
                    <i class="fas fa-plus"></i>
                </button>
            </div>
        </div>
        <div class="collapse" id="collapse-version-history">
            <div class="card-body p-0">

                @if($versionsWithDiff->isEmpty())
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-history fa-2x mb-2"></i>
                        <p class="mb-0">Nessuna versione registrata.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width:60px;">Ver.</th>
                                    <th>Data</th>
                                    <th>Autore</th>
                                    <th>Modifiche</th>
                                    <th style="width:140px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($versionsWithDiff as $item)
                                @php
                                    $v      = $item['version'];
                                    $fields = $item['changed_fields'];
                                    $isLatest = $loop->first;
                                @endphp
                                <tr>
                                    <td>
                                        <span class="badge {{ $isLatest ? 'badge-primary' : 'badge-light' }}">
                                            V{{ $v->version_number }}
                                        </span>
                                    </td>
                                    <td class="small">
                                        {{ $v->created_at->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="small">
                                        {{ $v->creator->name ?? '—' }}
                                    </td>
                                    <td class="small">
                                        @if($isLatest)
                                            <span class="text-muted">versione corrente</span>
                                        @elseif(empty($fields))
                                            <span class="text-muted">prima versione</span>
                                        @else
                                            @foreach($fields as $field)
                                                <span class="badge badge-warning mr-1">{{ $field }}</span>
                                            @endforeach
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        <button type="button"
                                                class="btn btn-xs btn-outline-info mr-1"
                                                wire:click="openModal({{ $v->id }})">
                                            <i class="fas fa-eye"></i> Visualizza
                                        </button>
                                        @unless($isLatest)
                                        <button type="button"
                                                class="btn btn-xs btn-outline-warning"
                                                wire:click="restoreVersion({{ $v->id }})"
                                                wire:confirm="Ripristinare la versione V{{ $v->version_number }}? Lo stato corrente verrà salvato come nuova versione nella cronologia."
                                                wire:loading.attr="disabled">
                                            <span wire:loading.remove wire:target="restoreVersion({{ $v->id }})">
                                                <i class="fas fa-undo"></i> Ripristina
                                            </span>
                                            <span wire:loading wire:target="restoreVersion({{ $v->id }})">
                                                <i class="fas fa-spinner fa-spin"></i>
                                            </span>
                                        </button>
                                        @endunless
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

            </div>
        </div>
    </div>

    {{-- Modale read-only versione --}}
    @if($showModal && $modalVersionModel)
    <div class="modal fade show d-block" tabindex="-1" role="dialog"
         style="background:rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-history mr-2"></i>
                        Versione V{{ $modalVersionModel->version_number }}
                        <small class="text-muted ml-2">
                            {{ $modalVersionModel->created_at->format('d/m/Y H:i') }}
                            @if($modalVersionModel->creator)
                                — {{ $modalVersionModel->creator->name }}
                            @endif
                        </small>
                    </h5>
                    <button type="button" class="close" wire:click="closeModal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">

                    <div class="form-group">
                        <label class="font-weight-bold">Testo domanda</label>
                        <p class="form-control-plaintext border rounded p-2">
                            {{ $modalVersionModel->question }}
                        </p>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">Risposta corretta</label>
                        <p>
                            @if($modalVersionModel->is_true)
                                <span class="badge badge-success">Vero</span>
                            @else
                                <span class="badge badge-danger">Falso</span>
                            @endif
                        </p>
                    </div>

                    @if($modalVersionModel->image)
                    <div class="form-group">
                        <label class="font-weight-bold">Immagine</label>
                        <div>
                            <img src="{{ Storage::url($modalVersionModel->image) }}"
                                 alt="Immagine versione"
                                 class="img-fluid rounded shadow-sm"
                                 style="max-width:300px;">
                        </div>
                    </div>
                    @endif

                    {{-- Diff vs versione corrente --}}
                    @php
                        $diffVsCurrent = [];
                        if ($modalVersionModel->question !== $question->question)       $diffVsCurrent[] = 'testo';
                        if ((bool)$modalVersionModel->is_true !== (bool)$question->is_true) $diffVsCurrent[] = 'risposta';
                        if ($modalVersionModel->image !== $question->image)             $diffVsCurrent[] = 'immagine';
                        if ($modalVersionModel->category_id !== $question->category_id) $diffVsCurrent[] = 'categoria';
                    @endphp
                    @if(!empty($diffVsCurrent))
                    <div class="alert alert-warning mb-0 mt-3">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        Questa versione differisce dalla corrente nei campi:
                        @foreach($diffVsCurrent as $f)
                            <strong>{{ $f }}</strong>{{ !$loop->last ? ', ' : '.' }}
                        @endforeach
                    </div>
                    @else
                    <div class="alert alert-success mb-0 mt-3">
                        <i class="fas fa-check-circle mr-1"></i>
                        Questa versione è identica allo stato corrente della domanda.
                    </div>
                    @endif

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closeModal">
                        Chiudi
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>
