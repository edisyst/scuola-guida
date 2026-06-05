@extends('layouts.admin')

@section('title', __('review.diagnostic_title'))

@section('content_header')@endsection

@section('content')
<div class="sg-wrapper">

    <div class="sg-header">
        <p class="sg-header-subtitle">{{ __('review.diagnostic_subtitle_alt') }}</p>
        <h1 class="sg-header-title">
            <i class="fas fa-stethoscope mr-2"></i> {{ __('review.diagnostic_title') }}
        </h1>
    </div>

    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-7">

            <div class="alert alert-info mb-4">
                <i class="fas fa-info-circle mr-1"></i>
                {{ __('review.diagnostic_info') }}
            </div>

            <livewire:diagnostic-test />

        </div>
    </div>

</div>
@endsection
