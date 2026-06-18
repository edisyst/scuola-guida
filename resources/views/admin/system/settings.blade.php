@extends('layouts.admin')

@section('title', __('system.settings_title'))

@section('content_header')@endsection

@section('content')
    <div class="sg-wrapper">

        <div class="sg-header">
            <h1 class="sg-header-title">{{ __('system.settings_title') }}</h1>
        </div>

        <form action="{{ route('admin.system.settings.update') }}" method="POST" enctype="multipart/form-data">
            @csrf

            {{-- Dati scuola --}}
            <div class="sg-card">
                <div class="sg-card-header">
                    <h3 class="sg-card-title">{{ __('system.section_school') }}</h3>
                </div>
                <div class="sg-card-body">

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
            <div class="sg-card">
                <div class="sg-card-header">
                    <h3 class="sg-card-title">{{ __('system.section_appearance') }}</h3>
                </div>
                <div class="sg-card-body">

                    @php
                        $appearanceMap = $appearance->keyBy('key');
                        $currentLogo       = $schoolMap->get('school.logo_path')?->value;
                        $currentLogoDark   = $schoolMap->get('school.logo_dark_path')?->value;
                        $currentAccent     = $appearanceMap->get('appearance.accent_color')?->value ?? '#3c8dbc';
                        $currentAccentDark = $appearanceMap->get('appearance.accent_color_dark')?->value ?? '#4aa3d4';
                        $currentFont       = $appearanceMap->get('appearance.font_family')?->value ?? 'system';
                        $currentRadius     = $appearanceMap->get('appearance.border_radius')?->value ?? 'default';
                        $skinAdmin      = $appearanceMap->get('appearance.sidebar_skin_admin')?->value ?? 'sidebar-dark-danger';
                        $skinEditor     = $appearanceMap->get('appearance.sidebar_skin_editor')?->value ?? 'sidebar-dark-primary';
                        $skinViewer     = $appearanceMap->get('appearance.sidebar_skin_viewer')?->value ?? 'sidebar-dark-warning';
                        $skinInstructor = $appearanceMap->get('appearance.sidebar_skin_instructor')?->value ?? 'sidebar-dark-success';
                        $skinOptions    = \App\Http\Requests\UpdateSystemSettingsRequest::sidebarSkins();
                        $fontOptions    = ['system', 'inter', 'roboto', 'open-sans'];
                        $radiusOptions  = ['square', 'default', 'rounded'];
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

                    {{-- Colore accent dark mode --}}
                    <div class="form-group" x-data="{ color: '{{ $currentAccentDark }}' }">
                        <label>{{ __('system.accent_color_dark') }}</label>
                        <div class="d-flex align-items-center" style="gap: 12px;">
                            <input type="color" x-model="color"
                                   style="width:48px; height:38px; padding:2px; border:1px solid #ccc; border-radius:4px; cursor:pointer;"
                                   @input="$refs.colorDarkText.value = color">
                            <input type="text" name="accent_color_dark" x-ref="colorDarkText"
                                   class="form-control @error('accent_color_dark') is-invalid @enderror"
                                   x-model="color" maxlength="7" style="width:120px;"
                                   pattern="^#[0-9A-Fa-f]{6}$">
                        </div>
                        <small class="text-muted">{{ __('system.accent_color_dark_hint') }}</small>
                        @error('accent_color_dark')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        {{-- Font --}}
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('system.font_family') }}</label>
                                <div x-data="{ font: '{{ $currentFont }}' }" class="d-flex align-items-center gap-2">
                                    <select name="font_family" class="form-control @error('font_family') is-invalid @enderror"
                                            x-model="font"
                                            style="font-family: var(--sg-font);"
                                            :style="{
                                                'font-family': font === 'system' ? 'system-ui, -apple-system, sans-serif' :
                                                               font === 'inter' ? '\"Inter\", sans-serif' :
                                                               font === 'roboto' ? '\"Roboto\", sans-serif' :
                                                               '\"Open Sans\", sans-serif'
                                            }">
                                        @php
                                            $fontMap = [
                                                'system' => 'system-ui, -apple-system, sans-serif',
                                                'inter' => '"Inter", sans-serif',
                                                'roboto' => '"Roboto", sans-serif',
                                                'open-sans' => '"Open Sans", sans-serif',
                                            ];
                                        @endphp
                                        @foreach($fontOptions as $opt)
                                            <option value="{{ $opt }}" @selected($currentFont === $opt)>
                                                {{ __('system.font_' . str_replace('-', '_', $opt)) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted" style="white-space: nowrap;"
                                           :style="{
                                               'font-family': font === 'system' ? 'system-ui, -apple-system, sans-serif' :
                                                              font === 'inter' ? '\"Inter\", sans-serif' :
                                                              font === 'roboto' ? '\"Roboto\", sans-serif' :
                                                              '\"Open Sans\", sans-serif'
                                           }">
                                        (preview)
                                    </small>
                                </div>
                                @error('font_family')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Border radius --}}
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('system.border_radius') }}</label>
                                <select name="border_radius" class="form-control @error('border_radius') is-invalid @enderror">
                                    @foreach($radiusOptions as $opt)
                                        <option value="{{ $opt }}" @selected($currentRadius === $opt)>
                                            {{ __('system.radius_' . $opt) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('border_radius')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Skin sidebar per ruolo --}}
                    <label class="font-weight-bold mt-2">{{ __('system.sidebar_skins') }}</label>
                    <div class="row">
                        @php
                            $skinFields = [
                                'sidebar_skin_admin'      => $skinAdmin,
                                'sidebar_skin_editor'     => $skinEditor,
                                'sidebar_skin_viewer'     => $skinViewer,
                                'sidebar_skin_instructor' => $skinInstructor,
                            ];
                            $skinColorMap = [
                                'sidebar-dark-primary'   => '#007bff',
                                'sidebar-dark-danger'    => '#dc3545',
                                'sidebar-dark-success'   => '#28a745',
                                'sidebar-dark-warning'   => '#ffc107',
                                'sidebar-dark-info'      => '#17a2b8',
                                'sidebar-dark-indigo'    => '#6610f2',
                                'sidebar-dark-navy'      => '#001f3f',
                                'sidebar-light-primary'  => '#007bff',
                                'sidebar-light-danger'   => '#dc3545',
                                'sidebar-light-success'  => '#28a745',
                                'sidebar-light-warning'  => '#ffc107',
                                'sidebar-light-info'     => '#17a2b8',
                            ];
                        @endphp
                        @foreach($skinFields as $name => $current)
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>{{ __('system.' . $name) }}</label>
                                    <div x-data="{
                                        skin: '{{ $current }}',
                                        skinColors: {
                                            @foreach($skinColorMap as $skinName => $color)
                                                '{{ $skinName }}': '{{ $color }}',
                                            @endforeach
                                        }
                                    }">
                                        <select name="{{ $name }}" class="form-control @error($name) is-invalid @enderror"
                                                x-model="skin"
                                                :style="{
                                                    'background-color': skin && skinColors[skin] ? skinColors[skin] : '#999',
                                                    'color': 'white',
                                                    'font-weight': '500'
                                                }">
                                            @foreach($skinOptions as $skin)
                                                <option value="{{ $skin }}" @selected($current === $skin)
                                                        style="background-color: {{ $skinColorMap[$skin] ?? '#999' }}; color: white; font-weight: 500;">
                                                    {{ $skin }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @error($name)
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        @endforeach
                    </div>

                </div>
            </div>

            <div class="mb-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save mr-1"></i>{{ __('system.save_settings') }}
                </button>
            </div>

        </form>

        {{-- Carosello homepage — FUORI dal form principale per evitare form annidati --}}
        @php $carouselImages = json_decode(setting('school.carousel_images', '[]'), true) ?? []; @endphp
        <div class="sg-card">
            <div class="sg-card-header">
                <h3 class="sg-card-title">{{ __('system.section_carousel') }}</h3>
            </div>
            <div class="sg-card-body">

                @if(count($carouselImages) > 0)
                    <p class="text-muted small mb-2">{{ __('system.carousel_current') }}</p>
                    <div class="d-flex flex-wrap mb-3" style="gap:12px;">
                        @foreach($carouselImages as $idx => $imgPath)
                            <div class="position-relative" style="width:200px;">
                                <img src="{{ Storage::url($imgPath) }}"
                                     alt="Slide {{ $idx + 1 }}"
                                     style="width:200px;height:63px;object-fit:cover;border-radius:6px;border:1px solid #dee2e6;">
                                <form action="{{ route('admin.system.settings.carousel.delete', $idx) }}"
                                      method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="btn btn-danger btn-sm position-absolute"
                                            style="top:2px;right:2px;padding:1px 5px;font-size:.7rem;"
                                            onclick="return confirm('{{ __('system.carousel_delete_confirm') }}')">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if(count($carouselImages) < 4)
                    <form action="{{ route('admin.system.settings.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <label>{{ __('system.carousel_upload', ['n' => 4 - count($carouselImages)]) }}</label>
                            <input type="file" name="carousel_images[]"
                                   class="form-control-file @error('carousel_images.*') is-invalid @enderror"
                                   accept=".jpg,.jpeg,.png,.webp"
                                   multiple>
                            <small class="text-muted">{{ __('system.carousel_hint') }}</small>
                        </div>
                        @error('carousel_images.*')
                            <div class="text-danger small mb-2">{{ $message }}</div>
                        @enderror
                        <button type="submit" class="btn btn-secondary btn-sm">
                            <i class="fas fa-upload mr-1"></i>{{ __('system.carousel_add_btn') }}
                        </button>
                    </form>
                @else
                    <p class="text-muted small mb-0">{{ __('system.carousel_full') }}</p>
                @endif
            </div>
        </div>

    </div>
@stop
