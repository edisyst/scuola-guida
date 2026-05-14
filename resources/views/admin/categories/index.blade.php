@extends('layouts.admin')

@section('title', 'Categorie')
@section('content_header')@endsection

@section('content')
<div class="sg-wrapper">

    <div class="sg-header sg-flex-between">
        <div>
            <p class="sg-header-subtitle">Catalogo</p>
            <h1 class="sg-header-title"><i class="fas fa-tags mr-2"></i> Categorie</h1>
        </div>
        @if(auth()->user()->canCreateCategory())
            <a href="{{ route('admin.categories.create') }}" class="sg-btn sg-btn-light sg-btn-sm">
                <i class="fas fa-plus"></i> Nuova categoria
            </a>
        @endif
    </div>

    <div class="sg-card">
        <div class="table-responsive">
            <table id="categories-table" class="sg-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Slug</th>
                        <th>Domande</th>
                        <th style="width:160px;text-align:right;">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($categories as $category)
                    <tr>
                        <td class="sg-text-muted">{{ $category->id }}</td>
                        <td><strong>{{ $category->name }}</strong></td>
                        <td class="sg-text-muted">{{ $category->slug }}</td>
                        <td>
                            @if($category->questions_count > 0)
                                <span class="sg-badge sg-badge-info">{{ $category->questions_count }}</span>
                            @else
                                <span class="sg-text-muted">—</span>
                            @endif
                        </td>
                        <td class="sg-actions-cell">
                            @if(auth()->user()->canEditCategory())
                                <a href="{{ route('admin.categories.edit', $category) }}" class="sg-btn-icon edit" title="Modifica">
                                    <i class="fas fa-edit"></i>
                                </a>
                            @endif
                            @if(auth()->user()->canDeleteCategory())
                                <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button class="sg-btn-icon delete" title="Elimina" onclick="return confirm('Sei sicuro?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('js')
    @parent
    <script>
        $(document).ready(function() {
            $('#categories-table').DataTable({
                pageLength: 10,
                order: [[0, 'desc']],
                columnDefs: [
                    { orderable: false, targets: 4 }
                ]
            });
        });
    </script>
@stop
