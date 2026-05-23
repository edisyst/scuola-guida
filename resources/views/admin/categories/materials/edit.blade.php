@extends('layouts.admin')

@section('title', 'Modifica materiale — ' . $category->name)
@section('content_header')@endsection

@section('content')
<div class="sg-wrapper-sm">

    <div class="sg-header sg-flex-between">
        <div>
            <p class="sg-header-subtitle">{{ $category->name }}</p>
            <h1 class="sg-header-title"><i class="fas fa-edit mr-2"></i> Modifica materiale</h1>
        </div>
        <a href="{{ route('admin.categories.materials.index', $category) }}" class="sg-btn sg-btn-light sg-btn-sm">
            <i class="fas fa-arrow-left"></i> Indietro
        </a>
    </div>

    <form action="{{ route('admin.categories.materials.update', [$category, $material]) }}"
          method="POST"
          enctype="multipart/form-data"
          x-data="materialForm('{{ old('type', $material->type) }}')"
          @submit.prevent="$el.submit()">
        @csrf
        @method('PUT')

        @include('admin.categories.materials.partials.form', ['editing' => true])

        <div class="sg-mt-3 sg-text-center">
            <button type="submit" class="sg-btn sg-btn-primary">
                <i class="fas fa-save"></i> Aggiorna
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    function materialForm(initialType) {
        return {
            type: initialType,
        };
    }
</script>
@endpush
