{{-- Tabella popolata via AJAX da admin.questions.data (DataTables) --}}
@extends('layouts.admin')

@section('title', 'Domande')
@section('content_header')@endsection

@section('content')
<div class="sg-wrapper">

    <div class="sg-header sg-flex-between">
        <div>
            <p class="sg-header-subtitle">Catalogo</p>
            <h1 class="sg-header-title"><i class="fas fa-question-circle mr-2"></i> Domande</h1>
        </div>
        @if(auth()->user()->canCreateQuestion())
            <div class="sg-header-actions flex-wrap">
                <a href="{{ route('admin.questions.create') }}" class="sg-btn sg-btn-light sg-btn-sm">
                    <i class="fas fa-plus"></i> Nuova
                </a>
                <a href="{{ route('admin.questions.export') }}" class="sg-btn sg-btn-light sg-btn-sm">
                    <i class="fas fa-file-excel"></i> Export
                </a>
                <a href="{{ route('admin.questions.template') }}" class="sg-btn sg-btn-light sg-btn-sm">
                    <i class="fas fa-download"></i> Template
                </a>
            </div>
        @endif
    </div>

    @if(auth()->user()->canCreateQuestion())
        <div class="sg-card sg-mb-3">
            <div class="sg-card-body" style="padding:1rem 1.25rem;">
                <form action="{{ route('admin.questions.import') }}" method="POST" enctype="multipart/form-data" class="sg-d-flex sg-gap-2 align-items-center flex-wrap">
                    @csrf
                    <span class="sg-label sg-mb-0 mr-2"><i class="fas fa-file-import"></i> Import Excel</span>
                    <input type="file" name="file" required class="sg-form-control" style="max-width:340px;">
                    <button class="sg-btn sg-btn-primary sg-btn-sm">
                        <i class="fas fa-upload"></i> Carica
                    </button>
                </form>
            </div>
        </div>
    @endif

    <div class="sg-card">
        <div class="sg-card-body" style="padding:1.25rem;">
            <div class="row sg-mb-2">
                <div class="col-md-3 sg-mb-1">
                    <select id="filter-category" class="sg-form-control">
                        <option value="">Tutte le categorie</option>
                        @foreach($categories as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                @if(!auth()->user()->isViewer())
                <div class="col-md-3 sg-mb-1">
                    <select id="filter-is-true" class="sg-form-control">
                        <option value="">Vero / Falso</option>
                        <option value="1">Vero</option>
                        <option value="0">Falso</option>
                    </select>
                </div>
                @endif
                <div class="col-md-3 sg-mb-1">
                    <select id="filter-image" class="sg-form-control">
                        <option value="">Tutte</option>
                        <option value="1">Con immagine</option>
                    </select>
                </div>
                @if(auth()->user()->canDeleteQuestion())
                <div class="col-md-3 sg-mb-1 sg-text-center">
                    <button id="bulk-delete" class="sg-btn sg-btn-danger sg-btn-sm">
                        <i class="fas fa-trash"></i> Elimina selezionati
                    </button>
                </div>
                @endif
            </div>

            <div class="table-responsive">
                <table id="questions-table" class="sg-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Categoria</th>
                            <th>Domanda</th>
                            @if(!auth()->user()->isViewer())
                                <th>Risposta</th>
                            @endif
                            <th>Img</th>
                            @if(!auth()->user()->isViewer())
                                <th>Azioni</th>
                            @endif
                            @if(auth()->user()->canDeleteQuestion())
                                <th><input type="checkbox" id="select-all"></th>
                            @endif
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Modal anteprima immagine domanda --}}
<div class="modal fade" id="questionImageModal" tabindex="-1" role="dialog" aria-labelledby="questionImageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width:540px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="questionImageModalLabel"></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Chiudi">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <img id="questionImageModalImg" src="" alt="" style="max-width:500px; max-height:500px; width:auto; height:auto;">
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
    @parent

    <script>
        $('#select-all').on('click', function() {
            $('.row-checkbox').prop('checked', this.checked);
        });

        // Apertura modal al click sulla miniatura (delegato perché DataTables rigenera il DOM)
        $(document).on('click', '#questions-table .question-thumb', function() {
            var url = $(this).data('full-src');
            if (!url) return;
            $('#questionImageModalImg').attr('src', url);
            $('#questionImageModalLabel').text($(this).data('question') || '');
            $('#questionImageModal').modal('show');
        });

        $('#questionImageModal').on('hidden.bs.modal', function() {
            $('#questionImageModalImg').attr('src', '');
            $('#questionImageModalLabel').text('');
        });

        $('#bulk-delete').click(function() {
            let ids = [];

            $('.row-checkbox:checked').each(function() {
                ids.push($(this).val());
            });

            if (!ids.length) {
                toastr.warning('Seleziona almeno un elemento');
                return;
            }

            if (!confirm('Sei sicuro?')) return;

            $.ajax({
                url: "{{ route('admin.questions.bulkDelete') }}",
                method: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    ids: ids
                },
                success: function() {
                    toastr.success('Eliminati');
                    $('#questions-table').DataTable().ajax.reload();
                }
            });
        });

        $(function() {
            let table = $('#questions-table').DataTable({
                pageLength: 25,
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('admin.questions.data') }}",
                    data: function (d) {
                        d.category_id = $('#filter-category').val();
                        d.is_true = $('#filter-is-true').val();
                        d.has_image = $('#filter-image').val();
                    }
                },

                columns: [
                    { data: 'id' },
                    { data: 'category' },
                    { data: 'question' },
                    @if(!auth()->user()->isViewer())
                    { data: 'is_true', orderable: false },
                    @endif
                    { data: 'image', orderable: false },
                    @if(!auth()->user()->isViewer())
                    { data: 'actions', orderable: false },
                    @endif
                    @if(auth()->user()->canDeleteQuestion())
                    { data: 'checkbox', orderable: false, searchable: false },
                    @endif
                ],
            });

            $('#filter-category, #filter-is-true, #filter-image').change(function() {
                table.draw();
            });
        });
    </script>
@stop
