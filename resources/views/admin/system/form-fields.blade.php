@extends('layouts.admin')

@section('title', __('forms.page_title'))

@section('content_header')@endsection

@section('content')
    <div class="sg-wrapper">

        <div class="sg-header">
            <h1 class="sg-header-title">{{ __('forms.page_title') }}</h1>
            <p class="sg-header-subtitle sg-mt-1">{{ __('forms.page_subtitle') }}</p>
        </div>

        <div class="sg-card">
            <div class="sg-card-body">
                @livewire('admin.form-fields-manager')
            </div>
        </div>

    </div>
@endsection
