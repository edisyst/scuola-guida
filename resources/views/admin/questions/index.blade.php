{{-- Tabella popolata via AJAX da admin.questions.data (DataTables) --}}
@extends('layouts.admin')

@section('title', __('questions.title'))
@section('content_header')@endsection

@section('content')
<div class="sg-wrapper">

    <div class="sg-header sg-flex-between">
        <div>
            <p class="sg-header-subtitle">{{ __('questions.subtitle') }}</p>
            <h1 class="sg-header-title"><i class="fas fa-question-circle mr-2"></i> {{ __('questions.title') }}</h1>
        </div>
        @if(auth()->user()->canCreateQuestion())
            <div class="sg-header-actions flex-wrap">
                <a href="{{ route('admin.questions.create') }}" class="sg-btn sg-btn-light sg-btn-sm">
                    <i class="fas fa-plus"></i> {{ __('questions.action_new') }}
                </a>
                <a href="{{ route('admin.questions.export') }}" class="sg-btn sg-btn-light sg-btn-sm">
                    <i class="fas fa-file-excel"></i> {{ __('questions.action_export') }}
                </a>
                <a href="{{ route('admin.questions.template') }}" class="sg-btn sg-btn-light sg-btn-sm">
                    <i class="fas fa-download"></i> {{ __('questions.action_template') }}
                </a>
                <a href="{{ route('admin.questions.mit-import') }}" class="sg-btn sg-btn-light sg-btn-sm">
                    <i class="fas fa-file-import"></i> {{ __('questions.action_import_mit') }}
                </a>
            </div>
        @endif
    </div>

    @if(auth()->user()->canCreateQuestion())
        <div class="sg-card sg-mb-3">
            <div class="sg-card-body" style="padding:1rem 1.25rem;">
                <form action="{{ route('admin.questions.import') }}" method="POST" enctype="multipart/form-data" class="sg-d-flex sg-gap-2 align-items-center flex-wrap">
                    @csrf
                    <span class="sg-label sg-mb-0 mr-2"><i class="fas fa-file-import"></i> {{ __('questions.import_excel') }}</span>
                    <input type="file" name="file" required class="sg-form-control" style="max-width:min(340px, 100%);">
                    <button class="sg-btn sg-btn-primary sg-btn-sm">
                        <i class="fas fa-upload"></i> {{ __('questions.action_upload') }}
                    </button>
                </form>
            </div>
        </div>
    @endif

    <div class="sg-card">
        <div class="sg-card-body" style="padding:1.25rem;">
            <div class="row sg-mb-2">
                <div class="col-12 col-md-3 sg-mb-1">
                    <select id="filter-category" class="sg-form-control">
                        <option value="">{{ __('questions.filter_all_categories') }}</option>
                        @foreach($categories as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                @if(!auth()->user()->isViewer())
                <div class="col-12 col-md-3 sg-mb-1">
                    <select id="filter-is-true" class="sg-form-control">
                        <option value="">{{ __('questions.filter_true_false') }}</option>
                        <option value="1">{{ __('questions.filter_true') }}</option>
                        <option value="0">{{ __('questions.filter_false') }}</option>
                    </select>
                </div>
                @endif
                <div class="col-12 col-md-3 sg-mb-1">
                    <select id="filter-image" class="sg-form-control">
                        <option value="">{{ __('questions.filter_all') }}</option>
                        <option value="1">{{ __('questions.filter_with_image') }}</option>
                    </select>
                </div>
                @if(auth()->user()->canDeleteQuestion())
                <div class="col-12 col-md-3 sg-mb-1 sg-text-center">
                    <button id="bulk-delete" class="sg-btn sg-btn-danger sg-btn-sm">
                        <i class="fas fa-trash"></i> {{ __('questions.bulk_delete') }}
                    </button>
                </div>
                @endif
            </div>

            <div class="table-responsive">
                <table id="questions-table" class="sg-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>{{ __('questions.col_category') }}</th>
                            <th>{{ __('questions.col_text') }}</th>
                            @if(!auth()->user()->isViewer())
                                <th>{{ __('questions.col_answer') }}</th>
                            @endif
                            <th>Img</th>
                            @if(!auth()->user()->isViewer())
                                <th>{{ __('questions.col_actions') }}</th>
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
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document" style="max-width:540px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-truncate" id="questionImageModalLabel"></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Chiudi">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                {{-- img-fluid: si adatta a viewport ridotte; max-height limita altezza --}}
                <img id="questionImageModalImg" src="" alt="" class="img-fluid" style="max-height:500px;">
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
                toastr.warning('{{ __('questions.select_at_least_one') }}');
                return;
            }

            if (!confirm('{{ __('questions.confirm_bulk_delete') }}')) return;

            $.ajax({
                url: "{{ route('admin.questions.bulkDelete') }}",
                method: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    ids: ids
                },
                success: function() {
                    toastr.success('{{ __('questions.deleted_success') }}');
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
