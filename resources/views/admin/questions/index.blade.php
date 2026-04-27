@extends('layouts.admin')

@section('header', 'Domande')

@section('content')

    <a href="{{ route('questions.create') }}" class="btn btn-primary mb-3">
        Nuova Domanda
    </a>

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
        </tr>
        </thead>
    </table>

@endsection

@section('js')
    @parent

    <script>
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
                ],
            });

            // 🔥 trigger reload al cambio filtro
            $('#filter-category, #filter-is-true, #filter-image').change(function() {
                table.draw();
            });
        });
    </script>
@stop
