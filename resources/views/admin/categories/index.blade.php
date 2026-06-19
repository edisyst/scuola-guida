@extends('layouts.admin')

@section('title', __('categories.title'))
@section('content_header')@endsection

@section('content')
<div class="sg-wrapper-fluid">

    <div class="sg-header sg-flex-between">
        <div>
            <p class="sg-header-subtitle">{{ __('categories.subtitle') }}</p>
            <h1 class="sg-header-title"><i class="fas fa-tags mr-2"></i> {{ __('categories.title') }}</h1>
        </div>
        @if(auth()->user()->canCreateCategory())
            <a href="{{ route('admin.categories.create') }}" class="sg-btn sg-btn-light sg-btn-sm">
                <i class="fas fa-plus"></i> {{ __('categories.create') }}
            </a>
        @endif
    </div>

    <div class="sg-card">
        <div class="table-responsive">
            <table id="categories-table" class="sg-table">
                <thead>
                    <tr>
                        <th>{{ __('categories.col_id') }}</th>
                        <th>{{ __('categories.col_name') }}</th>
                        @if(!auth()->user()->isViewer())
                            <th>{{ __('categories.col_slug') }}</th>
                        @endif
                        <th>{{ __('categories.col_questions') }}</th>
                        @if(!auth()->user()->isViewer())
                            <th class="text-right" style="width:160px;">{{ __('categories.col_actions') }}</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                @foreach($categories as $category)
                    <tr>
                        <td class="sg-text-muted">{{ $category->id }}</td>
                        <td><strong>{{ $category->name }}</strong></td>
                        @if(!auth()->user()->isViewer())
                            <td class="sg-text-muted">{{ $category->slug }}</td>
                        @endif
                        <td>
                            @if($category->questions_count > 0)
                                <span class="sg-badge sg-badge-info">{{ $category->questions_count }}</span>
                            @else
                                <span class="sg-text-muted">—</span>
                            @endif
                        </td>
                        @if(!auth()->user()->isViewer())
                            <td class="sg-actions-cell">
                                @if(auth()->user()->canEditCategory())
                                    <a href="{{ route('admin.categories.materials.index', $category) }}" class="sg-btn-icon" title="Materiale didattico" style="color:#6c757d;">
                                        <i class="fas fa-book-open"></i>
                                    </a>
                                    <a href="{{ route('admin.categories.edit', $category) }}" class="sg-btn-icon edit" title="Modifica">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                @endif
                                @if(auth()->user()->canDeleteCategory())
                                    <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button class="sg-btn-icon delete" title="Elimina" onclick="return confirm('{{ __('categories.confirm_delete') }}')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        @endif
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
                pageLength: 25,
                order: [[0, 'desc']],
                @if(!auth()->user()->isViewer())
                columnDefs: [
                    { orderable: false, targets: 4 }
                ]
                @endif
            });
        });
    </script>
@stop
