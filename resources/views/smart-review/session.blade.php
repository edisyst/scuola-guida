@extends('layouts.admin')
@section('title', __('review.session_title'))
@section('content_header')@endsection
@section('content')
<div class="sg-wrapper">
    <div class="sg-header sg-flex-between">
        <div>
            <p class="sg-header-subtitle">{{ __('review.session_subtitle') }}</p>
            <h1 class="sg-header-title">
                <i class="fas fa-brain mr-2"></i> {{ __('review.session_title') }}
            </h1>
        </div>
        <div class="sg-header-actions">
            <a href="{{ route('viewer.smart-review.index') }}" class="sg-btn sg-btn-light sg-btn-sm">
                <i class="fas fa-arrow-left mr-1"></i> {{ __('review.back_overview') }}
            </a>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6">
            <livewire:smart-review :category-id="$categoryId" />
        </div>
    </div>
</div>
@endsection
