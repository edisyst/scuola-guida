@extends('layouts.admin')

@section('title', __('features.page_title'))

@section('content_header')@endsection

@section('content')
<div class="sg-wrapper">

    <div class="sg-header">
        <p class="sg-header-subtitle">{{ __('system.settings_title') }}</p>
        <h1 class="sg-header-title">
            <i class="fas fa-toggle-on mr-2"></i> {{ __('features.page_title') }}
        </h1>
    </div>

    <livewire:admin.feature-toggles />

</div>
@endsection

@section('js')
@parent
<script>
window.addEventListener('feature-notify', function (e) {
    if (typeof toastr !== 'undefined') {
        toastr[e.detail.type](e.detail.message);
    }
});
</script>
@endsection
