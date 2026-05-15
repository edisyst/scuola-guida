@extends('layouts.admin')

@section('title', 'Media Manager')
@section('content_header')@endsection

@section('content')
<div class="sg-wrapper">

    <div class="sg-header sg-flex-between">
        <div>
            <p class="sg-header-subtitle">Immagini</p>
            <h1 class="sg-header-title"><i class="fas fa-images mr-2"></i> Media Manager</h1>
        </div>
    </div>

    @livewire('admin.media-manager')

</div>
@endsection
