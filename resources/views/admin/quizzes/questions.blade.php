@extends('layouts.admin')

@section('header', 'Gestione Domande Quiz')

@section('content')

    <div class="card">

        <div class="card-header d-flex justify-content-between">

            <h5>{{ $quiz->title }}</h5>

            <a href="{{ route('admin.quizzes.index') }}"
               class="btn btn-secondary btn-sm">
                Indietro
            </a>

        </div>

        <div class="card-body">

            {{-- FILTRI --}}
            <div class="row mb-3">

                <div class="col-md-4">
                    <select id="filter-category" class="form-control">
                        <option value="">-- Tutte le categorie --</option>

                        @foreach(\App\Models\Category::all() as $cat)
                            <option value="{{ $cat->id }}">
                                {{ $cat->name }}
                            </option>
                        @endforeach

                    </select>
                </div>

            </div>

            {{-- TABELLA --}}
            <table id="questions-table" class="table table-bordered">

                <thead>
                <tr>
                    <th>ID</th>
                    <th>Domanda</th>
                    <th>Categoria</th>
                    <th>Stato</th>
                    <th>Azione</th>
                </tr>
                </thead>

            </table>

        </div>

    </div>

@endsection

@section('js')
    @parent

    <script>
        let table;

        $(document).ready(function () {

            table = $('#questions-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('admin.quizzes.questions.data', $quiz) }}",
                    data: function (d) {
                        d.category_id = $('#filter-category').val();
                    }
                },
                columns: [
                    { data: 'id' },
                    { data: 'question' },
                    // { data: 'question', render: function(data) {  return data.length > 80 ? data.substr(0, 80) + '...' : data; }}
                    { data: 'category' },
                    { data: 'status', orderable: false },
                    { data: 'action', orderable: false }
                ]
            });

            // filtro
            $('#filter-category').change(function () {
                table.ajax.reload();
            });

        });

        // ADD
        $(document).on('click', '.btn-add', function () {

            let id = $(this).data('id');

            $.post("{{ route('admin.quizzes.questions.add', $quiz) }}", {
                _token: "{{ csrf_token() }}",
                question_id: id
            }, function () {
                toastr.success('Aggiunta');
                table.ajax.reload();
            });

        });

        // REMOVE
        $(document).on('click', '.btn-remove', function () {

            let id = $(this).data('id');

            $.post("{{ route('admin.quizzes.questions.remove', $quiz) }}", {
                _token: "{{ csrf_token() }}",
                question_id: id
            }, function () {
                toastr.warning('Rimossa');
                table.ajax.reload();
            });

        });

    </script>

@stop
