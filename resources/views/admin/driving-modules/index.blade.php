@extends('layouts.admin')

@section('page-title', __('driving.title_modules'))

@section('content_header')@endsection

@section('content')
<div class="sg-wrapper">

    {{-- Intestazione con titolo e bottone nuovo modulo --}}
    <div class="sg-header sg-flex-between mb-3">
        <div>
            <h1 class="sg-header-title">
                <i class="fas fa-car mr-2"></i> {{ __('driving.title_modules') }}
            </h1>
        </div>
        <div>
            <a href="{{ route('admin.driving-modules.create', ['license_type_id' => $selectedLicenseTypeId]) }}"
               class="sg-btn sg-btn-primary">
                <i class="fas fa-plus mr-1"></i> {{ __('driving.btn_new_module') }}
            </a>
        </div>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Chiudi">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle mr-1"></i> {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Chiudi">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    {{-- Filtro tipo patente --}}
    <div class="sg-card mb-3">
        <div class="p-3">
            <form method="GET" action="{{ route('admin.driving-modules.index') }}" class="form-inline">
                <label for="license_type_filter" class="mr-2">{{ __('driving.filter_label') }}</label>
                <select id="license_type_filter"
                        name="license_type_id"
                        class="form-control mr-2"
                        onchange="this.form.submit()">
                    <option value="">{{ __('driving.filter_all_types') }}</option>
                    @foreach($licenseTypes as $type)
                        <option value="{{ $type->id }}"
                            {{ (string)$selectedLicenseTypeId === (string)$type->id ? 'selected' : '' }}>
                            {{ $type->name }}
                        </option>
                    @endforeach
                </select>
                <noscript>
                    <button type="submit" class="sg-btn sg-btn-secondary sg-btn-sm">
                        {{ __('common.filter') }}
                    </button>
                </noscript>
            </form>
        </div>
    </div>

    {{-- Tabella moduli o empty state --}}
    @if($modules->isEmpty())
        <div class="text-center py-5">
            <i class="fas fa-car fa-3x text-muted mb-3 d-block"></i>
            <p class="text-muted mb-2">{{ __('driving.modules_empty') }}</p>
            <p class="text-muted mb-3">{{ __('driving.modules_empty_hint') }}</p>
            <a href="{{ route('admin.driving-modules.create', ['license_type_id' => $selectedLicenseTypeId]) }}"
               class="sg-btn sg-btn-primary">
                <i class="fas fa-plus mr-1"></i> {{ __('driving.btn_new_module') }}
            </a>
        </div>
    @else
        <div class="sg-card">
            <div class="sg-card-header">
                <h3 class="sg-card-title">{{ __('driving.title_modules') }}</h3>
            </div>
            <div class="table-responsive">
                <table class="sg-table">
                    <thead>
                        <tr>
                            <th>{{ __('driving.col_code') }}</th>
                            <th>{{ __('driving.col_name') }}</th>
                            <th>{{ __('driving.col_license_type') }}</th>
                            <th>{{ __('driving.col_required_hours') }}</th>
                            <th>{{ __('driving.col_sessions') }}</th>
                            <th>{{ __('driving.col_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($modules as $module)
                            <tr>
                                <td><strong>{{ $module->code }}</strong></td>
                                <td>{{ $module->name }}</td>
                                <td>
                                    @if($module->licenseType)
                                        <span class="sg-badge sg-badge-secondary">
                                            {{ $module->licenseType->name }}
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>{{ $module->required_hours }} h</td>
                                <td>{{ $module->driving_sessions_count }}</td>
                                <td>
                                    {{-- Modifica --}}
                                    <a href="{{ route('admin.driving-modules.edit', $module) }}"
                                       class="sg-btn sg-btn-info sg-btn-sm mr-1">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    {{-- Elimina --}}
                                    <form method="POST"
                                          action="{{ route('admin.driving-modules.destroy', $module) }}"
                                          style="display: inline;"
                                          onsubmit="return confirm('{{ __('driving.module_delete_confirm') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="sg-btn sg-btn-danger sg-btn-sm">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

</div>
@endsection
