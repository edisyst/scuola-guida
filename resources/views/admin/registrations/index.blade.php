@extends('layouts.admin')

@section('title', __('enrollments.reg_title'))
@section('content_header')@endsection

@section('content')
<div class="sg-wrapper-fluid">

    <div class="sg-header sg-flex-between">
        <div>
            <p class="sg-header-subtitle">{{ __('enrollments.reg_subtitle') }}</p>
            <h1 class="sg-header-title"><i class="fas fa-id-card mr-2"></i> {{ __('enrollments.reg_title') }}</h1>
        </div>
        @if($pendingCount > 0)
            <span class="sg-badge sg-badge-warning">
                <i class="fas fa-clock"></i> {{ $pendingCount }} {{ __('enrollments.status_pending') }}
            </span>
        @endif
    </div>

    <div class="sg-card sg-mb-3">
        <div class="sg-card-body sg-flex flex-wrap" style="gap:8px;">
            <a href="{{ route('admin.registrations.index') }}"
               class="sg-btn sg-btn-sm {{ !$status ? 'sg-btn-primary' : 'sg-btn-light' }}">{{ __('enrollments.filter_all') }}</a>
            @foreach(\App\Models\User::REG_STATUSES as $key => $label)
                @if($key === \App\Models\User::REG_NONE) @continue @endif
                <a href="{{ route('admin.registrations.index', ['status' => $key]) }}"
                   class="sg-btn sg-btn-sm {{ $status === $key ? 'sg-btn-primary' : 'sg-btn-light' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>

    <div class="sg-card">
        @if($registrations->isEmpty())
            <div class="sg-table-empty">{{ __('enrollments.no_registrations') }}</div>
        @else
            <div class="table-responsive">
                <table class="sg-table">
                    <thead>
                        <tr>
                            <th>{{ __('enrollments.col_id') }}</th>
                            <th>{{ __('enrollments.col_user') }}</th>
                            <th>{{ __('enrollments.col_email') }}</th>
                            <th>{{ __('enrollments.col_fiscal') }}</th>
                            <th>{{ __('enrollments.col_status') }}</th>
                            <th>{{ __('enrollments.col_submitted') }}</th>
                            <th class="text-right">{{ __('enrollments.col_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($registrations as $u)
                            <tr>
                                <td class="sg-text-muted">{{ $u->id }}</td>
                                <td><strong>{{ $u->fullAnagraphicName() }}</strong></td>
                                <td class="sg-text-muted">{{ $u->email }}</td>
                                <td class="sg-text-muted">{{ $u->fiscal_code ?? '—' }}</td>
                                <td>@include('profile.partials.registration-status-badge', ['user' => $u])</td>
                                <td class="sg-text-muted">
                                    {{ $u->registration_submitted_at?->format('d/m/Y H:i') ?? '—' }}
                                </td>
                                <td class="text-right">
                                    {{-- bottoni azione: gap-2 evita che si tocchino --}}
                                    <div class="d-inline-flex gap-2 align-items-center">
                                        <a href="{{ route('admin.registrations.show', $u) }}"
                                           class="sg-btn sg-btn-light sg-btn-sm">
                                            <i class="fas fa-eye"></i> {{ __('enrollments.action_details') }}
                                        </a>
                                        @if($u->isRegistrationPending())
                                            <form method="POST" action="{{ route('admin.registrations.approve', $u) }}"
                                                  class="d-inline"
                                                  onsubmit="return confirm('{{ __('enrollments.confirm_approve_reg', ['name' => $u->fullAnagraphicName()]) }}');">
                                                @csrf
                                                <button class="sg-btn sg-btn-success sg-btn-sm">
                                                    <i class="fas fa-check"></i> {{ __('enrollments.action_approve') }}
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="sg-card-section">
                {{ $registrations->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
