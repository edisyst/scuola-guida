@extends('layouts.admin')

@section('title', __('forms.page_title'))

@section('content_header')
    <h1>{{ __('forms.page_title') }}</h1>
@stop

@section('content')
    <div class="container-fluid">
        <p class="text-muted sg-mb-4">{{ __('forms.page_subtitle') }}</p>

        <div class="card">
            <div class="card-body">
                @livewire('admin.form-fields-manager')
            </div>
        </div>
    </div>
@endsection
