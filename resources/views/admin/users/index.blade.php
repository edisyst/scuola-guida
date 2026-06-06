@extends('layouts.admin')

@section('title', __('users.title'))
@section('content_header')@endsection

@section('content')
<div class="sg-wrapper">

    <div class="sg-header sg-flex-between">
        <div>
            <h1 class="sg-header-title"><i class="fas fa-users mr-2"></i> {{ __('users.title') }}</h1>
            <p class="sg-header-subtitle sg-mt-1">{{ __('users.subtitle') }}</p>
        </div>
        <div class="sg-header-actions">
            <a href="{{ route('admin.roles.index') }}" class="sg-btn sg-btn-light sg-btn-sm">
                <i class="fas fa-user-shield"></i> {{ __('users.action_roles') }}
            </a>
            @if(auth()->user()->canCreateUser())
                <a href="{{ route('admin.users.create') }}" class="sg-btn sg-btn-success sg-btn-sm">
                    <i class="fas fa-plus"></i> {{ __('users.action_new') }}
                </a>
            @endif
        </div>
    </div>

    <div class="sg-card sg-mb-3">
        <div class="sg-card-body">
            <form method="GET" action="{{ route('admin.users.index') }}" class="row align-items-end">
                <div class="col-12 col-md-4">
                    <label class="sg-label mb-2">{{ __('users.filter_license_type') }}</label>
                    <select name="license_type_id" class="sg-form-control">
                        <option value="">{{ __('common.all') }}</option>
                        @foreach($licenseTypes as $lt)
                            <option value="{{ $lt->id }}" @selected($licenseTypeId == $lt->id)>{{ $lt->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <button type="submit" class="sg-btn sg-btn-primary sg-btn-sm" style="width:100%;">
                        <i class="fas fa-filter"></i> {{ __('common.filter') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="sg-card">
        <div class="table-responsive">
            <table class="sg-table">
                <thead>
                    <tr>
                        <th>{{ __('users.col_user') }}</th>
                        <th>{{ __('users.col_email') }}</th>
                        <th>{{ __('users.col_role') }}</th>
                        <th>{{ __('users.col_license_type') }}</th>
                        <th>{{ __('users.col_permissions') }}</th>
                        <th class="text-right" style="width:160px;">{{ __('users.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $u)
                        <tr>
                            <td>
                                <span class="sg-user-avatar">{{ strtoupper(substr($u->name, 0, 1)) }}</span>
                                <strong>{{ $u->name }}</strong>
                            </td>
                            <td class="sg-text-muted">{{ $u->email }}</td>
                            <td>
                                <span class="sg-badge-role role-{{ $u->role }}">{{ $u->role }}</span>
                            </td>
                            <td>
                                @if($u->activeLicenseType)
                                    <span class="badge badge-secondary">{{ $u->activeLicenseType->code }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($u->role !== \App\Models\User::ROLE_ADMIN && !empty($u->permissions))
                                    <span class="sg-badge sg-badge-info">+{{ count($u->permissions) }}</span>
                                @else
                                    <span class="sg-text-muted">—</span>
                                @endif
                            </td>
                            <td class="sg-actions-cell">
                                <a href="{{ route('admin.users.stats', $u) }}" class="sg-btn-icon" title="Statistiche">
                                    <i class="fas fa-chart-line"></i>
                                </a>

                                @if(auth()->user()->canEditUser())
                                    <a href="{{ route('admin.users.edit', $u) }}" class="sg-btn-icon edit" title="Modifica">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                @endif

                                @if(auth()->user()->canDeleteUser() && $u->id !== auth()->id())
                                    <form method="POST" action="{{ route('admin.users.destroy', $u) }}"
                                          class="d-inline" onsubmit="return confirm('{{ __('users.confirm_delete', ['name' => $u->name]) }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="sg-btn-icon delete" title="Elimina">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
            <div class="sg-card-section">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
