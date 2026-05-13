@extends('layouts.admin')

@section('header', 'Categorie')

@section('content')
    @if(auth()->user()->canCreateCategory())
    <a href="{{ route('admin.categories.create') }}" class="btn btn-primary mb-3">
        Nuova Categoria
    </a>
    @endif

    <table id="categories-table" class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Slug</th>
                <th>Domande</th>
                <th width="150">Azioni</th>
            </tr>
        </thead>
        <tbody>
        @foreach($categories as $category)
            <tr>
                <td>{{ $category->id }}</td>
                <td>{{ $category->name }}</td>
                <td>{{ $category->slug }}</td>
                <td>
                    @if($category->questions_count > 0)
                        <span class="badge badge-secondary" title="Numero domande">{{ $category->questions_count }}</span>
                    @endif
                </td>

                <td>
                    @if(auth()->user()->canEditCategory())
                        <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-sm btn-warning">Modifica</a>
                    @endif

                    @if(auth()->user()->canDeleteCategory())
                        <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-danger" onclick="return confirm('Sei sicuro?')">Elimina</button>
                        </form>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endsection

@section('js')
    @parent

    <script>
        $(document).ready(function() {
            $('#categories-table').DataTable({
                pageLength: 10,
                order: [[0, 'desc']],
                columnDefs: [
                    { orderable: false, targets: 4 } // azioni
                ]
            });
        });
    </script>
@stop
