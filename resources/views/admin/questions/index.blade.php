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
            <th width="150">Azioni</th>
        </tr>
        </thead>
        <tbody>
        @foreach($questions as $q)
            <tr>
                <td>{{ $q->id }}</td>
                <td>{{ $q->category->name }}</td>
                <td>{{ \Illuminate\Support\Str::limit($q->question, 50) }}</td>
                <td>
                    @if($q->is_true)
                        <span class="badge badge-success">Vero</span>
                    @else
                        <span class="badge badge-danger">Falso</span>
                    @endif
                </td>
                <td>
                    @if($q->image)
                        <img src="{{ asset('storage/'.$q->image) }}" width="50">
                    @endif
                </td>
                <td>
                    <a href="{{ route('questions.edit', $q) }}" class="btn btn-sm btn-warning">Modifica</a>

                    <form action="{{ route('questions.destroy', $q) }}" method="POST" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-danger" onclick="return confirm('Sei sicuro?')">
                            Elimina
                        </button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>

@endsection

@section('js')
    @parent

    <script>
        $(function() {
            $('#questions-table').DataTable({
                pageLength: 10,
                order: [[0, 'desc']],
                columnDefs: [
                    { orderable: false, targets: [4,5] } // img + azioni non ordinabili
                ],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/it-IT.json'
                }
            });
        });
    </script>
@stop
