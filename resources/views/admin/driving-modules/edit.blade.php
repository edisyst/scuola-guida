@extends('layouts.admin')

@section('page-title', __('driving.edit_title'))

@section('content_header')@endsection

@section('content')
<div class="sg-wrapper">

    {{-- Intestazione --}}
    <div class="sg-header sg-flex-between mb-3">
        <div>
            <h1 class="sg-header-title">
                <i class="fas fa-edit mr-2"></i> {{ __('driving.edit_title') }}
            </h1>
            <p class="sg-header-subtitle sg-mt-1">
                {{ $module->code }} — {{ $module->name }}
            </p>
        </div>
        <div>
            <a href="{{ route('admin.driving-modules.index', ['license_type_id' => $module->license_type_id]) }}"
               class="sg-btn sg-btn-light sg-btn-sm">
                <i class="fas fa-arrow-left mr-1"></i> {{ __('driving.btn_cancel') }}
            </a>
        </div>
    </div>

    {{-- Form modifica --}}
    <div class="sg-card">
        <div class="sg-card-header">
            <h3 class="sg-card-title">{{ __('driving.edit_title') }}</h3>
        </div>
        <div class="p-4">
            <form method="POST" action="{{ route('admin.driving-modules.update', $module) }}">
                @csrf
                @method('PUT')

                <div class="row">

                    {{-- Tipo patente: sola lettura, non modificabile --}}
                    <div class="col-md-6 form-group">
                        <label>{{ __('driving.field_license_type') }}</label>
                        <p class="form-control-plaintext">
                            <span class="sg-badge sg-badge-secondary">
                                {{ $module->licenseType->name ?? '—' }}
                            </span>
                        </p>
                        {{-- Campo hidden per garantire che il license_type_id sia presente se il controller lo richiede --}}
                        {{-- Non viene inviato: il controller non deve permetterne la modifica --}}
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
                               value="{{ old('code', $module->code) }}"
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
                               value="{{ old('sort_order', $module->sort_order) }}"
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
                               value="{{ old('name', $module->name) }}"
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
                               value="{{ old('required_hours', $module->required_hours) }}"
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
                              class="form-control @error('description') is-invalid @enderror">{{ old('description', $module->description) }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Pulsanti --}}
                <div class="mt-3">
                    <button type="submit" class="sg-btn sg-btn-primary mr-2">
                        <i class="fas fa-save mr-1"></i> {{ __('driving.btn_save') }}
                    </button>
                    <a href="{{ route('admin.driving-modules.index', ['license_type_id' => $module->license_type_id]) }}"
                       class="sg-btn sg-btn-light">
                        {{ __('driving.btn_cancel') }}
                    </a>
                </div>

            </form>
        </div>
    </div>

</div>
@endsection
