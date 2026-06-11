@extends('layouts.admin')

@section('title', __('system.settings_title'))

@section('content_header')
    <h1>{{ __('system.settings_title') }}</h1>
@stop

@section('content')
    <div class="container-fluid">
        <form action="{{ route('admin.system.settings.update') }}" method="POST" enctype="multipart/form-data">
            @csrf

            {{-- Dati scuola --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ __('system.section_school') }}</h3>
                </div>
                <div class="card-body">

                    @php
                        $schoolMap = $school->keyBy('key');
                    @endphp

                    <div class="form-group">
                        <label>{{ __('system.school_name') }}</label>
                        <input type="text" name="school_name" class="form-control @error('school_name') is-invalid @enderror"
                               value="{{ old('school_name', $schoolMap->get('school.name')?->value) }}" maxlength="150">
                        @error('school_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label>{{ __('system.school_tagline') }}</label>
                        <input type="text" name="school_tagline" class="form-control @error('school_tagline') is-invalid @enderror"
                               value="{{ old('school_tagline', $schoolMap->get('school.tagline')?->value) }}"
                               placeholder="{{ __('system.tagline_placeholder') }}" maxlength="255">
                        @error('school_tagline')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label>{{ __('system.school_address') }}</label>
                        <input type="text" name="school_address" class="form-control @error('school_address') is-invalid @enderror"
                               value="{{ old('school_address', $schoolMap->get('school.address')?->value) }}" maxlength="255">
                        @error('school_address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('system.school_phone') }}</label>
                                <input type="text" name="school_phone" class="form-control @error('school_phone') is-invalid @enderror"
                                       value="{{ old('school_phone', $schoolMap->get('school.phone')?->value) }}" maxlength="20">
                                @error('school_phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('system.school_email') }}</label>
                                <input type="email" name="school_email" class="form-control @error('school_email') is-invalid @enderror"
                                       value="{{ old('school_email', $schoolMap->get('school.email')?->value) }}" maxlength="150">
                                @error('school_email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>{{ __('system.school_license_number') }}</label>
                        <input type="text" name="school_license_number" class="form-control @error('school_license_number') is-invalid @enderror"
                               value="{{ old('school_license_number', $schoolMap->get('school.license_number')?->value) }}" maxlength="50">
                        @error('school_license_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                </div>
            </div>

            {{-- Aspetto --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ __('system.section_appearance') }}</h3>
                </div>
                <div class="card-body">

                    @php
                        $appearanceMap = $appearance->keyBy('key');
                        $currentLogo     = $schoolMap->get('school.logo_path')?->value;
                        $currentLogoDark = $schoolMap->get('school.logo_dark_path')?->value;
                        $currentAccent   = $appearanceMap->get('appearance.accent_color')?->value ?? '#3c8dbc';
                    @endphp

                    {{-- Logo chiaro --}}
                    <div class="form-group">
                        <label>{{ __('system.logo') }}</label>
                        @if($currentLogo)
                            <div class="mb-2">
                                <small class="text-muted d-block">{{ __('system.logo_current') }}</small>
                                <img src="{{ Storage::url($currentLogo) }}" alt="Logo" style="max-height:60px;">
                            </div>
                        @endif
                        <input type="file" name="logo" class="form-control-file @error('logo') is-invalid @enderror"
                               accept=".jpg,.jpeg,.png,.svg">
                        @error('logo')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Logo dark --}}
                    <div class="form-group">
                        <label>{{ __('system.logo_dark') }}</label>
                        @if($currentLogoDark)
                            <div class="mb-2">
                                <small class="text-muted d-block">{{ __('system.logo_dark_current') }}</small>
                                <img src="{{ Storage::url($currentLogoDark) }}" alt="Logo dark" style="max-height:60px; background:#333; padding:4px;">
                            </div>
                        @endif
                        <input type="file" name="logo_dark" class="form-control-file @error('logo_dark') is-invalid @enderror"
                               accept=".jpg,.jpeg,.png,.svg">
                        @error('logo_dark')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Colore accent --}}
                    <div class="form-group" x-data="{ color: '{{ $currentAccent }}' }">
                        <label>{{ __('system.accent_color') }}</label>
                        <div class="d-flex align-items-center" style="gap: 12px;">
                            <input type="color" x-model="color"
                                   style="width:48px; height:38px; padding:2px; border:1px solid #ccc; border-radius:4px; cursor:pointer;"
                                   @input="$refs.colorText.value = color">
                            <input type="text" name="accent_color" x-ref="colorText"
                                   class="form-control @error('accent_color') is-invalid @enderror"
                                   x-model="color" maxlength="7" style="width:120px;"
                                   pattern="^#[0-9A-Fa-f]{6}$">
                        </div>
                        @error('accent_color')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                </div>
            </div>

            <div class="mb-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save mr-1"></i>{{ __('system.save_settings') }}
                </button>
            </div>

        </form>
    </div>
@stop
