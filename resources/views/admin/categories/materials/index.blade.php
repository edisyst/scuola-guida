@extends('layouts.admin')

@section('title', 'Materiale didattico — ' . $category->name)
@section('content_header')@endsection

@section('content')
<div class="sg-wrapper">

    <div class="sg-header sg-flex-between">
        <div>
            <p class="sg-header-subtitle">{{ $category->name }}</p>
            <h1 class="sg-header-title"><i class="fas fa-book-open mr-2"></i> Materiale didattico</h1>
        </div>
        <div class="d-flex" style="gap:8px;">
            <a href="{{ route('admin.categories.materials.create', $category) }}" class="sg-btn sg-btn-primary sg-btn-sm">
                <i class="fas fa-plus"></i> Aggiungi materiale
            </a>
            <a href="{{ route('admin.categories.index') }}" class="sg-btn sg-btn-light sg-btn-sm">
                <i class="fas fa-arrow-left"></i> Categorie
            </a>
        </div>
    </div>

    <div class="sg-card">
        @if($materials->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="fas fa-book-open fa-3x mb-3"></i>
                <p>Nessun materiale didattico per questa categoria.</p>
                <a href="{{ route('admin.categories.materials.create', $category) }}" class="sg-btn sg-btn-primary">
                    <i class="fas fa-plus"></i> Aggiungi il primo materiale
                </a>
            </div>
        @else
            <p class="text-muted px-3 pt-3 mb-1" style="font-size:.85rem;">
                <i class="fas fa-grip-vertical mr-1"></i> Trascina le righe per riordinare
            </p>
            <ul id="sortable-materials" class="list-unstyled m-0">
                @foreach($materials as $material)
                    <li data-id="{{ $material->id }}"
                        class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom">
                        <div class="d-flex align-items-center" style="gap:12px;flex:1;min-width:0;">
                            <i class="fas fa-grip-vertical text-muted" style="cursor:grab;"></i>
                            @php
                                $badge = match($material->type) {
                                    'pdf'  => ['info', 'fa-file-pdf', 'PDF'],
                                    'link' => ['success', 'fa-link', 'Link'],
                                    'note' => ['secondary', 'fa-sticky-note', 'Nota'],
                                };
                            @endphp
                            <span class="badge badge-{{ $badge[0] }}">
                                <i class="fas {{ $badge[1] }} mr-1"></i>{{ $badge[2] }}
                            </span>
                            <strong class="text-truncate" style="max-width:400px;">{{ $material->title }}</strong>
                        </div>
                        <div class="d-flex align-items-center" style="gap:8px;flex-shrink:0;">
                            <small class="text-muted">
                                {{ $material->creator?->name ?? '—' }}
                                &middot;
                                {{ $material->created_at->format('d/m/Y') }}
                            </small>
                            @if(auth()->user()->canEditCategory())
                                <a href="{{ route('admin.categories.materials.edit', [$category, $material]) }}"
                                   class="sg-btn-icon edit" title="Modifica">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('admin.categories.materials.destroy', [$category, $material]) }}"
                                      method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button class="sg-btn-icon delete" title="Elimina"
                                            onclick="return confirm('Eliminare questo materiale?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
@endsection

@section('js')
@parent
@if($materials->isNotEmpty())
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
    new Sortable(document.getElementById('sortable-materials'), {
        animation: 150,
        handle: '.fa-grip-vertical',
        onEnd: function () {
            const ids = [];
            document.querySelectorAll('#sortable-materials li').forEach(function (li) {
                ids.push(li.dataset.id);
            });
            $.post("{{ route('admin.categories.materials.reorder', $category) }}", {
                _token: "{{ csrf_token() }}",
                ids: ids
            }, function () {
                toastr.success('Ordine aggiornato');
            });
        }
    });
</script>
@endif
@endsection
