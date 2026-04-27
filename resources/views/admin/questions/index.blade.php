@extends('layouts.admin')

@section('header', 'Domande')

@section('content')

    <a href="{{ route('questions.create') }}" class="btn btn-primary mb-3">
        Nuova Domanda
    </a>

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
            $('#questions-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('questions.data') }}",

                columns: [
                    { data: 'id' },
                    { data: 'category' },
                    { data: 'question' },
                    { data: 'is_true', orderable: false, searchable: false },
                    { data: 'image', orderable: false, searchable: false },
                    { data: 'actions', orderable: false, searchable: false },
                ],

                pageLength: 10,
                order: [[0, 'desc']],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/it-IT.json'
                }
            });
        });
    </script>
@stop
