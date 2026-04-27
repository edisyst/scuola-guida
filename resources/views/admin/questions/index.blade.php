@extends('layouts.admin')

@section('header', 'Domande')

@section('content')

    <a href="{{ route('questions.create') }}" class="btn btn-primary mb-3">
        Nuova Domanda
    </a>
    <a href="{{ route('questions.export') }}" class="btn btn-success mb-3">
        Export Excel
    </a>
    <a href="{{ route('questions.template') }}" class="btn btn-info mb-3">
        Scarica Template
    </a>

    <form action="{{ route('questions.import') }}" method="POST" enctype="multipart/form-data" class="mb-3">
        @csrf
        <input type="file" name="file" required>
        <button class="btn btn-primary">Import Excel</button>
    </form>

    <button id="bulk-delete" class="btn btn-danger mb-3">Elimina selezionati</button>

    <div class="row mb-3">
        <div class="col-md-3">
            <select id="filter-category" class="form-control">
                <option value="">Tutte categorie</option>
                @foreach($categories as $c)
                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3">
            <select id="filter-is-true" class="form-control">
                <option value="">Tutte</option>
                <option value="1">Vero</option>
                <option value="0">Falso</option>
            </select>
        </div>

        <div class="col-md-3">
            <select id="filter-image" class="form-control">
                <option value="">Tutte</option>
                <option value="1">Con immagine</option>
            </select>
        </div>
    </div>

    <table id="questions-table" class="table table-bordered table-striped">
        <thead>
        <tr>
            <th>ID</th>
            <th>Categoria</th>
            <th>Domanda</th>
            <th>Risposta</th>
            <th>Img</th>
            <th>Azioni</th>
            <th><input type="checkbox" id="select-all"></th>
        </tr>
        </thead>
    </table>

@endsection

@section('js')
    @parent

    <script>
        $('#select-all').on('click', function() {
            $('.row-checkbox').prop('checked', this.checked);
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
                url: "{{ route('questions.bulkDelete') }}",
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
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('questions.data') }}",
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
                    { data: 'is_true', orderable: false },
                    { data: 'image', orderable: false },
                    { data: 'actions', orderable: false },
                    { data: 'checkbox', orderable: false, searchable: false },
                ],
            });

            // 🔥 trigger reload al cambio filtro
            $('#filter-category, #filter-is-true, #filter-image').change(function() {
                table.draw();
            });
        });
    </script>
@stop
