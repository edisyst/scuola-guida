@extends('layouts.admin')

@section('title', $isAdminView ? __('dashboard.admin_title', ['name' => $user->name]) : __('dashboard.title'))

@section('content_header')@endsection

@section('content')
<div class="sg-wrapper">

    <div class="sg-header sg-flex-between">
        <div>
            <p class="sg-header-subtitle">
                @if($isAdminView)
                    {{ __('dashboard.admin_subtitle') }}
                @else
                    {{ __('dashboard.subtitle') }}
                @endif
            </p>
            <h1 class="sg-header-title">
                <i class="fas fa-chart-line mr-2"></i>
                @if($isAdminView)
                    {{ __('dashboard.admin_title', ['name' => $user->name]) }}
                @else
                    {{ __('dashboard.title') }}
                @endif
            </h1>
            <p class="sg-text-muted sg-mt-1">
                <i class="far fa-clock"></i>
                {{ __('dashboard.updated_at') }}
                {{ \Illuminate\Support\Carbon::parse($stats['generated_at'])->diffForHumans() }}
                — {{ __('dashboard.cache_ttl', ['minutes' => \App\Services\UserStatsService::CACHE_TTL / 60]) }}
            </p>
        </div>
        <div class="sg-header-actions">
            @if($isAdminView)
                <a href="{{ route('admin.users.index') }}" class="sg-btn sg-btn-light sg-btn-sm">
                    <i class="fas fa-arrow-left"></i> {{ __('dashboard.back_users') }}
                </a>
            @endif
            <form method="POST" action="{{ route('dashboard.refresh', $user) }}" class="d-inline">
                @csrf
                @if($isAdminView)
                    <input type="hidden" name="as_admin" value="1">
                @endif
                <button type="submit" class="sg-btn sg-btn-primary sg-btn-sm">
                    <i class="fas fa-sync-alt"></i> {{ __('dashboard.refresh') }}
                </button>
            </form>
        </div>
    </div>

    @if(!$isAdminView && !empty($nextSession))
        <div class="info-box {{ $nextSession->enrollment_status === 'upcoming' ? 'bg-gradient-warning' : 'bg-gradient-success' }} mb-3"
             @if($nextSession->enrollment_status === 'upcoming' && $nextSession->enrollments_open_at)
             x-data="countdown({{ $nextSession->enrollments_open_at->timestamp }})" x-init="start()"
             @endif
        >
            <span class="info-box-icon"><i class="fas fa-calendar-{{ $nextSession->enrollment_status === 'upcoming' ? 'alt' : 'check' }}"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">{{ __('dashboard.next_session') }}</span>
                <span class="info-box-number">{{ $nextSession->title }}</span>
                <span class="progress-description">
                    @if(in_array($nextSession->enrollment_status, ['open', 'not_scheduled']))
                        @if($nextSession->enrollments_close_at)
                            {{ __('dashboard.enrollments_open_until', ['date' => $nextSession->enrollments_close_at->format('d/m/Y')]) }}
                        @else
                            {{ __('dashboard.enrollments_open') }}
                        @endif
                    @elseif($nextSession->enrollment_status === 'upcoming' && $nextSession->enrollments_open_at)
                        {{ __('dashboard.opens_in') }} <strong x-text="display">{{ $nextSession->enrollments_open_at->format('d/m/Y H:i') }}</strong>
                    @endif
                    &mdash; <a href="{{ route('calendar.index') }}" class="{{ $nextSession->enrollment_status === 'upcoming' ? 'text-dark' : 'text-white' }}"><u>{{ __('dashboard.see_calendar') }}</u></a>
                </span>
            </div>
        </div>
    @endif

    @if(!$isAdminView && !empty($reviewErrorsCount))
        <a href="{{ route('viewer.review-errors.index') }}"
           class="info-box bg-gradient-warning mb-3 text-dark"
           style="text-decoration:none;">
            <span class="info-box-icon"><i class="fas fa-exclamation-triangle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">{{ __('dashboard.errors_to_review') }}</span>
                <span class="info-box-number">{{ $reviewErrorsCount }}</span>
                <span class="progress-description">{{ __('dashboard.errors_description') }}</span>
            </div>
        </a>
    @endif

    @if(!$isAdminView && ($dueToday ?? 0) > 0)
        <a href="{{ route('viewer.smart-review.session') }}"
           class="info-box bg-gradient-primary mb-3 text-white"
           style="text-decoration:none;">
            <span class="info-box-icon"><i class="fas fa-brain"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">{{ __('dashboard.smart_review_due') }}</span>
                <span class="info-box-number">{{ $dueToday }}</span>
                <span class="progress-description">{{ __('dashboard.smart_review_description') }}</span>
            </div>
        </a>
    @endif

    @if(!$isAdminView && isset($currentStreak) && feature('gamification_enabled'))
        @php
            $hasActivityToday = $currentStreak > 0 && isset($activityToday) && $activityToday;
            $atRisk = ($currentStreak > 0) && !($activityToday ?? false);
        @endphp
        <a href="{{ route('viewer.profile.badges') }}"
           class="info-box {{ $currentStreak > 0 ? 'bg-gradient-warning' : 'bg-light border' }} mb-3 text-{{ $currentStreak > 0 ? 'dark' : 'muted' }}"
           style="text-decoration:none;">
            <span class="info-box-icon">
                <i class="fas fa-fire{{ $currentStreak === 0 ? '-alt' : '' }}"></i>
            </span>
            <div class="info-box-content">
                <span class="info-box-text">{{ __('dashboard.streak_title') }}</span>
                <span class="info-box-number">
                    @if($currentStreak === 0)
                        0 {{ trans_choice('common.unit_days', 0) }}
                    @else
                        {{ $currentStreak }} {{ trans_choice('common.unit_days', $currentStreak) }}
                        @if($atRisk)
                            <span class="badge badge-danger ml-2" style="font-size:0.7rem;">{{ __('dashboard.streak_at_risk') }}</span>
                        @endif
                    @endif
                </span>
                <span class="progress-description">
                    @if($currentStreak === 0)
                        {{ __('dashboard.streak_start') }}
                    @elseif($atRisk)
                        {{ __('dashboard.streak_no_study') }}
                    @else
                        {{ __('dashboard.streak_best', ['count' => $longestStreak . ' ' . trans_choice('common.unit_days', $longestStreak)]) }}
                    @endif
                    &mdash; <u>{{ __('dashboard.see_badges') }}</u>
                </span>
            </div>
        </a>
    @endif

    @if(!$isAdminView && ($hasDiagnostic ?? false) === false && $stats['total_attempts'] === 0)
        <a href="{{ route('viewer.diagnostic.show') }}"
           class="info-box bg-gradient-info mb-3 text-white"
           style="text-decoration:none;">
            <span class="info-box-icon"><i class="fas fa-stethoscope"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">{{ __('dashboard.diagnostic_prompt') }}</span>
                <span class="info-box-number" style="font-size:1rem;">{{ __('dashboard.diagnostic_title') }}</span>
                <span class="progress-description">
                    {{ __('dashboard.diagnostic_desc') }}
                </span>
            </div>
        </a>
    @endif

    @if($stats['total_attempts'] === 0)
        <div class="sg-card sg-mt-3">
            <div class="sg-card-body sg-text-center p-5">
                <i class="fas fa-inbox text-muted" style="font-size:48px;"></i>
                <h3 class="sg-mt-2">{{ __('dashboard.no_attempts') }}</h3>
                <p class="sg-text-muted">
                    @if($isAdminView)
                        {{ __('dashboard.no_attempts_admin') }}
                    @else
                        {{ __('dashboard.no_attempts_self') }}
                    @endif
                </p>
                @unless($isAdminView)
                    {{-- entry point quiz: catalogo dei quiz confermati per iscrizione --}}
                    <a href="{{ route('quiz.confirmed.index') }}" class="sg-btn sg-btn-success sg-mt-2">
                        <i class="fas fa-clipboard-list"></i> {{ __('dashboard.start_quiz') }}
                    </a>
                @endunless
            </div>
        </div>
    @else

        {{-- KPI --}}
        <div class="row sg-grid-row">

            <div class="col-lg-3 col-6 sg-grid-col">
                <div class="sg-stat-card">
                    <div class="sg-stat-icon grad-blue"><i class="fas fa-clipboard-check"></i></div>
                    <div>
                        <div class="sg-stat-value">{{ $stats['total_attempts'] }}</div>
                        <div class="sg-stat-label">{{ __('dashboard.kpi_total') }}</div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6 sg-grid-col">
                <div class="sg-stat-card">
                    <div class="sg-stat-icon grad-green"><i class="fas fa-percentage"></i></div>
                    <div>
                        <div class="sg-stat-value">{{ $stats['avg_percentage'] }}%</div>
                        <div class="sg-stat-label">{{ __('dashboard.kpi_average') }}</div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6 sg-grid-col">
                <div class="sg-stat-card">
                    <div class="sg-stat-icon grad-orange"><i class="fas fa-trophy"></i></div>
                    <div>
                        <div class="sg-stat-value">{{ $stats['best_percentage'] }}%</div>
                        <div class="sg-stat-label">{{ __('dashboard.kpi_best') }}</div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6 sg-grid-col">
                <div class="sg-stat-card">
                    <div class="sg-stat-icon grad-red"><i class="fas fa-check-double"></i></div>
                    <div>
                        <div class="sg-stat-value">{{ $stats['pass_rate'] }}%</div>
                        <div class="sg-stat-label">
                            {{ __('dashboard.kpi_pass_rate') }}
                            <small class="sg-text-muted">({{ $stats['passed_count'] }}/{{ $stats['total_attempts'] }})</small>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- Riga 2: durata, risposte --}}
        <div class="row sg-grid-row">

            <div class="col-lg-4 col-md-6 sg-grid-col">
                <div class="sg-stat-card">
                    <div class="sg-stat-icon grad-blue"><i class="fas fa-stopwatch"></i></div>
                    <div>
                        <div class="sg-stat-value">{{ gmdate('i:s', $stats['avg_duration']) }}</div>
                        <div class="sg-stat-label">{{ __('dashboard.avg_duration') }}</div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6 sg-grid-col">
                <div class="sg-stat-card">
                    <div class="sg-stat-icon grad-green"><i class="fas fa-check"></i></div>
                    <div>
                        <div class="sg-stat-value">{{ $stats['total_correct'] }}/{{ $stats['total_questions'] }}</div>
                        <div class="sg-stat-label">{{ __('dashboard.correct_answers') }}</div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6 sg-grid-col">
                <div class="sg-stat-card">
                    <div class="sg-stat-icon grad-orange"><i class="fas fa-calendar-alt"></i></div>
                    <div>
                        <div class="sg-stat-value" style="font-size:1.2rem;">
                            {{ $stats['last_attempt_at'] ? \Illuminate\Support\Carbon::parse($stats['last_attempt_at'])->diffForHumans() : '—' }}
                        </div>
                        <div class="sg-stat-label">{{ __('dashboard.last_attempt') }}</div>
                    </div>
                </div>
            </div>

        </div>

        {{-- Grafici --}}
        <div class="row sg-grid-row">

            <div class="col-12 col-md-7 sg-grid-col">
                <div class="sg-card">
                    <div class="sg-card-header">
                        <h2 class="sg-card-header-title">{{ __('dashboard.chart_trend') }}</h2>
                    </div>
                    <div class="sg-card-body">
                        <canvas id="dailyChart" height="120"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-5 sg-grid-col">
                <div class="sg-card">
                    <div class="sg-card-header">
                        <h2 class="sg-card-header-title">{{ __('dashboard.chart_results') }}</h2>
                    </div>
                    <div class="sg-card-body">
                        <canvas id="passChart" height="120"></canvas>
                    </div>
                </div>
            </div>

        </div>

        {{-- Tabella per-quiz --}}
        @if(!empty($stats['avg_by_quiz']))
            <div class="sg-card sg-mt-3">
                <div class="sg-card-header">
                    <h2 class="sg-card-header-title">{{ __('dashboard.table_by_quiz') }}</h2>
                </div>
                <div class="table-responsive">
                    <table class="sg-table">
                        <thead>
                            <tr>
                                <th>{{ __('dashboard.col_quiz') }}</th>
                                <th class="text-right">{{ __('dashboard.col_attempts') }}</th>
                                <th class="text-right">{{ __('dashboard.col_avg_pct') }}</th>
                                <th class="text-right">{{ __('dashboard.col_best_pct') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stats['avg_by_quiz'] as $row)
                                <tr>
                                    <td>{{ $row['title'] }}</td>
                                    <td class="text-right">{{ $row['attempts'] }}</td>
                                    <td class="text-right">
                                        <span class="sg-badge {{ $row['avg_pct'] >= 60 ? 'sg-badge-success' : 'sg-badge-warning' }}">
                                            {{ $row['avg_pct'] }}%
                                        </span>
                                    </td>
                                    <td class="text-right">{{ $row['best_pct'] }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- Ultimi tentativi --}}
        @if(!empty($stats['latest_attempts']))
            <div class="sg-card sg-mt-3">
                <div class="sg-card-header">
                    <h2 class="sg-card-header-title">{{ __('dashboard.recent_title') }}</h2>
                </div>
                <div class="table-responsive">
                    <table class="sg-table">
                        <thead>
                            <tr>
                                <th>{{ __('dashboard.col_quiz') }}</th>
                                <th>{{ __('dashboard.col_date') }}</th>
                                <th class="text-right">{{ __('dashboard.col_score') }}</th>
                                <th class="text-right">{{ __('dashboard.col_pct') }}</th>
                                <th>{{ __('dashboard.col_result') }}</th>
                                <th class="text-right">{{ __('dashboard.col_duration') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stats['latest_attempts'] as $a)
                                <tr>
                                    <td>{{ $a['quiz_title'] }}</td>
                                    <td class="sg-text-muted">
                                        {{ \Illuminate\Support\Carbon::parse($a['created_at'])->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="text-right">{{ $a['score'] }}/{{ $a['total_questions'] }}</td>
                                    <td class="text-right"><strong>{{ $a['percentage'] }}%</strong></td>
                                    <td>
                                        @if($a['is_passed'])
                                            <span class="sg-badge sg-badge-success">{{ __('dashboard.passed_badge') }}</span>
                                        @else
                                            <span class="sg-badge sg-badge-danger">{{ __('dashboard.failed_badge') }}</span>
                                        @endif
                                    </td>
                                    <td class="text-right sg-text-muted">
                                        {{ $a['duration'] ? gmdate('i:s', $a['duration']) : '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

    @endif

    @auth
    @if(!$isAdminView && auth()->user()->isViewer())
    {{-- PWA install banner (viewer only, non standalone) --}}
    <div x-data="{
            show: false,
            init() {
                if (window.matchMedia('(display-mode: standalone)').matches) return;
                const dismissed = parseInt(localStorage.getItem('pwa-dismiss') || '0', 10);
                if (dismissed > Date.now()) return;
                if (window.__pwaInstallPrompt) { this.show = true; return; }
                document.addEventListener('pwa:installable', () => { this.show = true; });
            },
            install() {
                const prompt = window.__pwaInstallPrompt;
                if (!prompt) return;
                prompt.prompt();
                prompt.userChoice.then(() => { window.__pwaInstallPrompt = null; this.show = false; });
            },
            dismiss() {
                this.show = false;
                localStorage.setItem('pwa-dismiss', Date.now() + 7 * 24 * 60 * 60 * 1000);
            }
         }"
         x-show="show"
         x-cloak
         x-transition
         class="card mt-3" style="border-left: 4px solid var(--sg-primary);">
        <div class="card-body d-flex align-items-center justify-content-between flex-wrap" style="gap:.75rem;">
            <div class="d-flex align-items-center" style="gap:.75rem;">
                <div style="width:40px;height:40px;background:var(--sg-primary);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <img src="{{ asset('icons/icon.svg') }}" alt="ScuolaGUIDA" style="width:28px;height:28px;filter:brightness(10);">
                </div>
                <div>
                    <div style="font-weight:600;">{{ __('dashboard.pwa_title') }}</div>
                    <div class="text-muted" style="font-size:.85rem;">{{ __('dashboard.pwa_desc') }}</div>
                </div>
            </div>
            <div class="d-flex" style="gap:.5rem;">
                <button class="sg-btn sg-btn-primary sg-btn-sm" @click="install()">
                    <i class="fas fa-download"></i> {{ __('dashboard.pwa_install') }}
                </button>
                <button class="sg-btn sg-btn-light sg-btn-sm" @click="dismiss()">
                    {{ __('dashboard.pwa_dismiss') }}
                </button>
            </div>
        </div>
    </div>
    @endif
    @endauth

</div>
@endsection

@section('js')
@parent

@php
    // Etichette tradotte passate al JS per il countdown
    $enrollmentsOpenLabel = __('dashboard.enrollments_open_now');
@endphp
<script>
const enrollmentsOpenLabel = "{{ $enrollmentsOpenLabel }}";

function countdown(targetTimestamp) {
    return {
        display: '',
        intervalId: null,
        start() {
            this.update();
            this.intervalId = setInterval(() => this.update(), 1000);
        },
        update() {
            const diff = targetTimestamp - Math.floor(Date.now() / 1000);
            if (diff <= 0) {
                this.display = enrollmentsOpenLabel;
                clearInterval(this.intervalId);
                return;
            }
            const d = Math.floor(diff / 86400);
            const h = Math.floor((diff % 86400) / 3600);
            const m = Math.floor((diff % 3600) / 60);
            this.display = d > 0 ? `${d}g ${h}h ${m}m` : `${h}h ${m}m`;
        }
    };
}
</script>

@if($stats['total_attempts'] > 0)
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const dailyData = @json($stats['daily_chart']);
    const passed = {{ $stats['passed_count'] }};
    const failed = {{ $stats['failed_count'] }};

    if (document.getElementById('dailyChart')) {
        new Chart(document.getElementById('dailyChart'), {
            type: 'line',
            data: {
                labels: dailyData.map(i => i.date),
                datasets: [
                    {
                        label: '{{ __("dashboard.chart_avg_pct") }}',
                        data: dailyData.map(i => i.avg_pct),
                        tension: 0.35,
                        borderColor: '#4361ee',
                        backgroundColor: '#4361ee22',
                        fill: true,
                        yAxisID: 'y',
                        borderWidth: 2,
                        pointRadius: 3,
                    },
                    {
                        label: '{{ __("dashboard.chart_attempts") }}',
                        data: dailyData.map(i => i.attempts),
                        type: 'bar',
                        backgroundColor: '#28a74566',
                        borderColor: '#28a745',
                        yAxisID: 'y1',
                        borderWidth: 1,
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: true, position: 'bottom' } },
                scales: {
                    y:  { beginAtZero: true, max: 100, position: 'left',  title: { display: true, text: '{{ __("dashboard.chart_avg_pct") }}' } },
                    y1: { beginAtZero: true, position: 'right', title: { display: true, text: '{{ __("dashboard.chart_attempts") }}' }, grid: { drawOnChartArea: false } },
                    x:  { grid: { display: false } }
                }
            }
        });
    }

    if (document.getElementById('passChart')) {
        new Chart(document.getElementById('passChart'), {
            type: 'doughnut',
            data: {
                labels: ['{{ __("dashboard.passed") }}', '{{ __("dashboard.not_passed") }}'],
                datasets: [{
                    data: [passed, failed],
                    backgroundColor: ['#28a745', '#dc3545'],
                    borderWidth: 0,
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: true, position: 'bottom' } }
            }
        });
    }
</script>
@endif

@endsection
