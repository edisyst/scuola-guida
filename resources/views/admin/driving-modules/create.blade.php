@extends('layouts.admin')

@section('page-title', __('driving.create_title'))

@section('content_header')@endsection

@section('content')
<div class="sg-wrapper-fluid">

    {{-- Intestazione --}}
    <div class="sg-header sg-flex-between mb-3">
        <div>
            <h1 class="sg-header-title">
                <i class="fas fa-plus mr-2"></i> {{ __('driving.create_title') }}
            </h1>
        </div>
        <div>
            <a href="{{ route('admin.driving-modules.index', ['license_type_id' => $selectedLicenseTypeId]) }}"
               class="sg-btn sg-btn-light sg-btn-sm">
                <i class="fas fa-arrow-left mr-1"></i> {{ __('driving.btn_cancel') }}
            </a>
        </div>
    </div>

    {{-- Form creazione --}}
    <div class="sg-card">
        <div class="sg-card-header">
            <h3 class="sg-card-title">{{ __('driving.create_title') }}</h3>
        </div>
        <div class="p-4">
            <form method="POST" action="{{ route('admin.driving-modules.store') }}">
                @csrf

                <div class="row">

                    {{-- Tipo patente --}}
                    <div class="col-md-6 form-group">
                        <label for="license_type_id">
                            {{ __('driving.field_license_type') }} <span class="text-danger">*</span>
                        </label>
                        <select id="license_type_id"
                                name="license_type_id"
                                class="form-control @error('license_type_id') is-invalid @enderror"
                                required>
                            <option value="">—</option>
                            @foreach($licenseTypes as $type)
                                <option value="{{ $type->id }}"
                                    {{ old('license_type_id', $selectedLicenseTypeId) == $type->id ? 'selected' : '' }}>
                                    {{ $type->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('license_type_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Codice --}}
                    <div class="col-md-3 form-group">
                        <label for="code">
                            {{ __('driving.field_code') }} <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                               id="code"
                               name="code"
                               maxlength="5"
                               value="{{ old('code') }}"
                               class="form-control @error('code') is-invalid @enderror"
                               required>
                        @error('code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Ordinamento --}}
                    <div class="col-md-3 form-group">
                        <label for="sort_order">{{ __('driving.field_sort_order') }}</label>
                        <input type="number"
                               id="sort_order"
                               name="sort_order"
                               min="0"
                               value="{{ old('sort_order', 0) }}"
                               class="form-control @error('sort_order') is-invalid @enderror">
                        @error('sort_order')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                </div>

                <div class="row">

                    {{-- Nome --}}
                    <div class="col-md-8 form-group">
                        <label for="name">
                            {{ __('driving.field_name') }} <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                               id="name"
                               name="name"
                               maxlength="100"
                               value="{{ old('name') }}"
                               class="form-control @error('name') is-invalid @enderror"
                               required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Ore richieste --}}
                    <div class="col-md-4 form-group">
                        <label for="required_hours">
                            {{ __('driving.field_required_hours') }} <span class="text-danger">*</span>
                        </label>
                        <input type="number"
                               id="required_hours"
                               name="required_hours"
                               step="0.5"
                               min="0.5"
                               max="10"
                               value="{{ old('required_hours', 1) }}"
                               class="form-control @error('required_hours') is-invalid @enderror"
                               required>
                        @error('required_hours')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                </div>

                {{-- Descrizione --}}
                <div class="form-group">
                    <label for="description">{{ __('driving.field_description') }}</label>
                    <textarea id="description"
                              name="description"
                              rows="3"
                              class="form-control @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Pulsanti --}}
                <div class="mt-3">
                    <button type="submit" class="sg-btn sg-btn-primary mr-2">
                        <i class="fas fa-save mr-1"></i> {{ __('driving.btn_save') }}
                    </button>
                    <a href="{{ route('admin.driving-modules.index', ['license_type_id' => $selectedLicenseTypeId]) }}"
                       class="sg-btn sg-btn-light">
                        {{ __('driving.btn_cancel') }}
                    </a>
                </div>

            </form>
        </div>
    </div>

</div>
@endsection
