@extends('layouts.admin')

@section('title', __('instructor.title'))
@section('content_header')@endsection

@section('content')
<div class="sg-wrapper">

    <div class="sg-header">
        <h1 class="sg-header-title"><i class="fas fa-user-graduate mr-2"></i> {{ __('instructor.title') }}</h1>
        <p class="sg-header-subtitle sg-mt-1">{{ __('instructor.subtitle') }}</p>
    </div>

    @if(empty($overview))
        <div class="sg-card">
            <div class="text-center py-5">
                <i class="fas fa-user-graduate fa-3x text-muted mb-3"></i>
                <p class="text-muted mb-1">{{ __('instructor.no_students') }}</p>
                <p class="text-muted small">{{ __('instructor.no_students_hint') }}</p>
            </div>
        </div>
    @else
        <div class="sg-card">
            <div class="table-responsive">
                <table class="sg-table">
                    <thead>
                        <tr>
                            <th>{{ __('instructor.col_student') }}</th>
                            <th>{{ __('instructor.col_last_attempt') }}</th>
                            <th>{{ __('instructor.col_last_score') }}</th>
                            <th>{{ __('instructor.col_streak') }}</th>
                            <th>{{ __('instructor.col_active_today') }}</th>
                            <th class="text-right" style="width:100px;">{{ __('instructor.col_detail') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($overview as $row)
                            <tr>
                                <td>
                                    <span class="sg-user-avatar">{{ strtoupper(substr($row['name'], 0, 1)) }}</span>
                                    <strong>{{ $row['name'] }}</strong>
                                    <br><small class="sg-text-muted">{{ $row['email'] }}</small>
                                </td>
                                <td class="sg-text-muted">
                                    {{ $row['last_attempt_at']
                                        ? \Carbon\Carbon::parse($row['last_attempt_at'])->diffForHumans()
                                        : '—' }}
                                </td>
                                <td>
                                    @if($row['last_score'])
                                        <span class="sg-badge {{ $row['last_score']['pct'] >= 60 ? 'sg-badge-success' : 'sg-badge-danger' }}">
                                            {{ $row['last_score']['pct'] }}%
                                        </span>
                                    @else
                                        <span class="sg-text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($row['streak'] > 0)
                                        <span class="sg-badge sg-badge-warning">
                                            <i class="fas fa-fire"></i> {{ $row['streak'] }}
                                        </span>
                                    @else
                                        <span class="sg-text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($row['active_today'])
                                        <i class="fas fa-circle text-success" title="{{ __('instructor.active_today_yes') }}"></i>
                                    @else
                                        <i class="fas fa-circle text-secondary" title="{{ __('instructor.active_today_no') }}"></i>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <a href="{{ route('instructor.students.show', $row['id']) }}"
                                       class="sg-btn sg-btn-primary sg-btn-sm">
                                        <i class="fas fa-eye"></i> {{ __('instructor.action_view') }}
                                    </a>
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
