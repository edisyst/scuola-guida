@extends('layouts.admin')

@section('page-title', __('study_content.title_edit'))

@section('content_header')@endsection

@section('content')
<div class="sg-wrapper">

    <div class="sg-header mb-3">
        <h1 class="sg-header-title">
            <i class="fas fa-book mr-2"></i> {{ __('study_content.title_edit') }}
        </h1>
    </div>

    <form method="POST" action="{{ route('study-contents.update', $studyContent) }}">
        @csrf
        @method('PUT')
        @include('study-contents._form', ['studyContent' => $studyContent])
    </form>

</div>
@endsection

@push('scripts')
<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
tinymce.init({
    selector: '#body',
    plugins: 'link lists image',
    toolbar: 'undo redo | bold italic | bullist numlist | link image',
    menubar: false,
    height: 400,
    file_picker_callback: function(callback, value, meta) {
        if (meta.filetype === 'image') {
            window.mediaPickerCallback = callback;
            document.getElementById('mediaPickerModal').style.display = 'flex';
        }
    }
});

function insertMediaUrl(url) {
    if (window.mediaPickerCallback) {
        window.mediaPickerCallback(url, { title: '' });
        window.mediaPickerCallback = null;
    }
    document.getElementById('mediaPickerModal').style.display = 'none';
}
</script>
@endpush
