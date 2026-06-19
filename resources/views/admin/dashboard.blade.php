@extends('layouts.admin')

@section('title', 'Statistiche')

@section('content_header')@endsection

@section('content')
<div class="sg-wrapper-fluid">

    <div class="sg-header">
        <p class="sg-header-subtitle">Panoramica</p>
        <h1 class="sg-header-title">Statistiche</h1>
    </div>

    {{-- KPI BOX --}}
    <div class="row sg-grid-row">

        <div class="col-lg-3 col-6 sg-grid-col">
            <div class="sg-stat-card">
                <div class="sg-stat-icon sg-stat-icon--accent"><i class="fas fa-users"></i></div>
                <div>
                    <div class="sg-stat-value">{{ $stats['users'] }}</div>
                    <div class="sg-stat-label">Utenti</div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6 sg-grid-col">
            <div class="sg-stat-card">
                <div class="sg-stat-icon sg-stat-icon--success"><i class="fas fa-question-circle"></i></div>
                <div>
                    <div class="sg-stat-value">{{ $stats['questions'] }}</div>
                    <div class="sg-stat-label">Domande</div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6 sg-grid-col">
            <div class="sg-stat-card">
                <div class="sg-stat-icon sg-stat-icon--warning"><i class="fas fa-folder-open"></i></div>
                <div>
                    <div class="sg-stat-value">{{ $stats['categories'] }}</div>
                    <div class="sg-stat-label">Categorie</div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6 sg-grid-col">
            <div class="sg-stat-card">
                <div class="sg-stat-icon sg-stat-icon--danger"><i class="fas fa-clipboard-list"></i></div>
                <div>
                    <div class="sg-stat-value">{{ $stats['quizzes'] }}</div>
                    <div class="sg-stat-label">Quiz</div>
                </div>
            </div>
        </div>

    </div>

    {{-- GRAFICI --}}
    <div class="row sg-grid-row">

        <div class="col-12 col-md-6 sg-grid-col">
            <div class="sg-card">
                <div class="sg-card-header">
                    <h2 class="sg-card-header-title">Domande ultimi 30 giorni</h2>
                </div>
                <div class="sg-card-body">
                    <canvas id="questionsChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 sg-grid-col">
            <div class="sg-card">
                <div class="sg-card-header">
                    <h2 class="sg-card-header-title">Utenti ultimi 30 giorni</h2>
                </div>
                <div class="sg-card-body">
                    <canvas id="usersChart"></canvas>
                </div>
            </div>
        </div>

    </div>

    {{-- ── STATO DEI CONTENUTI ─────────────────────────────────────────────── --}}
    <div class="sg-header" style="margin-top:1.5rem;">
        <h2 class="sg-card-header-title" style="font-size:1.1rem;">
            <i class="fas fa-layer-group mr-1"></i> {{ __('editor.content_state_title') }}
        </h2>
    </div>

    <div class="row sg-grid-row">

        {{-- Quiz per stato --}}
        @php $quizzesByState = $globalMetrics['quizzes_by_state']; @endphp
        <div class="col-lg-3 col-6 sg-grid-col">
            <div class="sg-stat-card">
                <div class="sg-stat-icon sg-stat-icon--muted"><i class="fas fa-clipboard-list"></i></div>
                <div>
                    <div class="sg-stat-value">{{ $quizzesByState['draft'] ?? 0 }}</div>
                    <div class="sg-stat-label">{{ __('editor.quiz_draft') }}</div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6 sg-grid-col">
            <div class="sg-stat-card">
                <div class="sg-stat-icon sg-stat-icon--success"><i class="fas fa-paper-plane"></i></div>
                <div>
                    <div class="sg-stat-value">{{ $quizzesByState['published'] ?? 0 }}</div>
                    <div class="sg-stat-label">{{ __('editor.quiz_published') }}</div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6 sg-grid-col">
            <div class="sg-stat-card">
                <div class="sg-stat-icon sg-stat-icon--accent"><i class="fas fa-trophy"></i></div>
                <div>
                    <div class="sg-stat-value">{{ $quizzesByState['confirmed'] ?? 0 }}</div>
                    <div class="sg-stat-label">{{ __('editor.quiz_confirmed') }}</div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6 sg-grid-col">
            <div class="sg-stat-card">
                <div class="sg-stat-icon sg-stat-icon--danger"><i class="fas fa-image"></i></div>
                <div>
                    <div class="sg-stat-value">{{ $globalMetrics['questions_without_image'] }}</div>
                    <div class="sg-stat-label">
                        <a href="{{ route('admin.questions.index', ['no_image' => 1]) }}" class="text-inherit">
                            {{ __('editor.questions_no_image') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- CATEGORIE + SEGNALAZIONI --}}
    <div class="row sg-grid-row">

        {{-- Bar chart categorie --}}
        <div class="col-12 col-md-6 sg-grid-col">
            <div class="sg-card" style="height:100%;">
                <div class="sg-card-header">
                    <h2 class="sg-card-header-title">{{ __('editor.categories_chart_title') }}</h2>
                </div>
                <div class="sg-card-body">
                    @if($globalMetrics['categories_by_question_count']->isEmpty())
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-tags fa-3x mb-2"></i>
                        <p>{{ __('editor.no_categories') }}</p>
                    </div>
                    @else
                    <canvas id="categoriesChart"></canvas>
                    @endif
                </div>
            </div>
        </div>

        {{-- Top domande segnalate --}}
        <div class="col-12 col-md-6 sg-grid-col">
            <div class="sg-card" style="height:100%;">
                <div class="sg-card-header">
                    <h2 class="sg-card-header-title">{{ __('editor.most_reported_title') }}</h2>
                </div>
                <div class="sg-card-body p-0">
                    @if($globalMetrics['most_reported_questions']->isEmpty())
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-flag fa-3x mb-2"></i>
                        <p>{{ __('editor.no_reports') }}</p>
                    </div>
                    @else
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>{{ __('editor.reports_col_question') }}</th>
                                    <th class="text-center" style="width:60px;">Segnalazioni</th>
                                    <th style="width:110px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($globalMetrics['most_reported_questions'] as $q)
                                <tr>
                                    <td class="text-truncate" style="max-width:260px;"
                                        title="{{ $q->question }}">
                                        {{ Str::limit($q->question, 70) }}
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-danger">{{ $q->pending_reports_count }}</span>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.questions.edit', $q->id) }}"
                                           class="sg-btn sg-btn-light sg-btn-sm mr-1">
                                            <i class="fas fa-pen"></i>
                                        </a>
                                        <a href="{{ route('admin.question-reports.index', ['question_id' => $q->id]) }}"
                                           class="sg-btn sg-btn-warning sg-btn-sm">
                                            <i class="fas fa-flag"></i>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
            </div>
        </div>

    </div>

    {{-- ULTIME SEGNALAZIONI DA GESTIRE --}}
    @if($globalMetrics['recently_reported']->isNotEmpty())
    <div class="row sg-grid-row">
        <div class="col-12 sg-grid-col">
            <div class="sg-card">
                <div class="sg-card-header">
                    <h2 class="sg-card-header-title">{{ __('editor.recent_reports_title') }}</h2>
                </div>
                <div class="sg-card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>{{ __('editor.reports_col_question') }}</th>
                                    <th>{{ __('editor.reports_col_type') }}</th>
                                    <th>{{ __('editor.reports_col_reporter') }}</th>
                                    <th>{{ __('editor.reports_col_date') }}</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($globalMetrics['recently_reported'] as $report)
                                <tr>
                                    <td class="text-truncate" style="max-width:280px;"
                                        title="{{ $report->question->question ?? '—' }}">
                                        {{ Str::limit($report->question->question ?? '—', 60) }}
                                    </td>
                                    <td>{{ $report->type }}</td>
                                    <td>{{ $report->user->name ?? '—' }}</td>
                                    <td>{{ $report->created_at->format('d/m/Y') }}</td>
                                    <td>
                                        <a href="{{ route('admin.question-reports.show', $report->id) }}"
                                           class="sg-btn sg-btn-primary sg-btn-sm">
                                            {{ __('editor.action_manage') }}
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="sg-card-footer text-right">
                    <a href="{{ route('admin.question-reports.index') }}" class="sg-btn sg-btn-light sg-btn-sm">
                        {{ __('editor.view_all_reports') }} <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>
@endsection

@section('js')
@parent

{{-- Chart.js CDN --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    const questionsData = @json($questionsChart);
    const usersData = @json($usersChart);

    function buildChart(canvasId, data, label, color) {
        new Chart(document.getElementById(canvasId), {
            type: 'line',
            data: {
                labels: data.map(i => i.date),
                datasets: [{
                    label: label,
                    data: data.map(i => i.total),
                    tension: 0.35,
                    borderColor: color,
                    backgroundColor: color + '22',
                    fill: true,
                    pointRadius: 3,
                    pointBackgroundColor: color,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: true, position: 'bottom' }
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f1f3f5' } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    buildChart('questionsChart', questionsData, 'Domande', '#28a745');
    buildChart('usersChart', usersData, 'Utenti', '#4361ee');

    // ── CATEGORIE BAR ─────────────────────────────────────────────────────────
    var catCanvas = document.getElementById('categoriesChart');
    if (catCanvas) {
        var catData = @json($globalMetrics['categories_by_question_count']->take(15)->values());
        new Chart(catCanvas, {
            type: 'bar',
            data: {
                labels: catData.map(function (c) { return c.name; }),
                datasets: [{
                    label: '{{ __('categories.col_questions') }}',
                    data: catData.map(function (c) { return c.questions_count; }),
                    backgroundColor: '#4361ee22',
                    borderColor: '#4361ee',
                    borderWidth: 1
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: { beginAtZero: true, grid: { color: '#f1f3f5' } },
                    y: { grid: { display: false } }
                }
            }
        });
    }
</script>

@endsection
