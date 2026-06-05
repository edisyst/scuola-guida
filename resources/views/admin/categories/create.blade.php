@extends('layouts.admin')

@section('title', __('categories.create'))
@section('content_header')@endsection

@section('content')
<div class="sg-wrapper-sm">

    <div class="sg-header sg-flex-between">
        <div>
            <p class="sg-header-subtitle">{{ __('categories.subtitle') }}</p>
            <h1 class="sg-header-title"><i class="fas fa-plus mr-2"></i> {{ __('categories.create') }}</h1>
        </div>
        <a href="{{ route('admin.categories.index') }}" class="sg-btn sg-btn-light sg-btn-sm">
            <i class="fas fa-arrow-left"></i> {{ __('common.back') }}
        </a>
    </div>

    <form action="{{ route('admin.categories.store') }}" method="POST">
        @csrf

        <div class="sg-card">
            <div class="sg-card-body">
                @include('admin.categories.partials.form')
            </div>
        </div>

        <div class="sg-mt-3 sg-text-center">
            <button class="sg-btn sg-btn-primary">
                <i class="fas fa-save"></i> {{ __('common.save') }}
            </button>
        </div>
    </form>
</div>
@endsection
