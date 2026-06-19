@extends('layouts.admin')

@section('title', __('editor.title'))
@section('content_header')@endsection

@section('content')
<div class="sg-wrapper-fluid">

    <div class="sg-header">
        <p class="sg-header-subtitle">{{ __('editor.subtitle') }}</p>
        <h1 class="sg-header-title"><i class="fas fa-pen-fancy mr-2"></i> {{ __('editor.title') }}</h1>
    </div>

    {{-- FILTRI PERIODO + SELEZIONE EDITOR (admin) --}}
    <div class="sg-card mb-3">
        <div class="sg-card-body">
            <form method="GET" action="{{ route('editor.dashboard') }}" id="filter-form">

                @if($editors !== null)
                <div class="form-group mb-3">
                    <label class="font-weight-bold">{{ __('editor.filter_editor') }}</label>
                    <select name="editor_id" id="editor_id" class="form-control" style="max-width:320px;">
                        <option value="">{{ __('editor.filter_editor_all') }}</option>
                        @foreach($editors as $e)
                        <option value="{{ $e->id }}" @selected($selectedEditorId == $e->id)>{{ $e->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div class="form-group mb-2">
                    <label class="font-weight-bold">{{ __('editor.filter_period') }}</label>
                    <div class="mt-1">
                        <button type="button" class="sg-btn sg-btn-light sg-btn-sm mr-1 mb-1" data-preset="month">{{ __('editor.preset_month') }}</button>
                        <button type="button" class="sg-btn sg-btn-light sg-btn-sm mr-1 mb-1" data-preset="quarter">{{ __('editor.preset_quarter') }}</button>
                        <button type="button" class="sg-btn sg-btn-light sg-btn-sm mr-1 mb-1" data-preset="year">{{ __('editor.preset_year') }}</button>
                    </div>
                </div>

                <div class="form-group mb-3">
                    <label for="license_type_id" class="font-weight-bold">{{ __('editor.filter_license_type') }}</label>
                    <select name="license_type_id" id="license_type_id" class="form-control" style="max-width:320px;">
                        <option value="">{{ __('editor.filter_license_type_all') }}</option>
                        @foreach($licenseTypes as $lt)
                        <option value="{{ $lt->id }}" @selected($selectedLicenseTypeId == $lt->id)>{{ $lt->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="from">{{ __('editor.filter_from') }}</label>
                            <input type="date" name="from" id="from" class="form-control"
                                   value="{{ $from->format('Y-m-d') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="to">{{ __('editor.filter_to') }}</label>
                            <input type="date" name="to" id="to" class="form-control"
                                   value="{{ $to->format('Y-m-d') }}">
                        </div>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <div class="form-group">
                            <button type="submit" class="sg-btn sg-btn-primary">
                                <i class="fas fa-search"></i> {{ __('editor.filter_submit') }}
                            </button>
                        </div>
                    </div>
                </div>

            </form>
        </div>
    </div>

    {{-- ── PRODUZIONE ───────────────────────────────────────────────────────── --}}
    <div class="sg-header" style="margin-top:1.5rem;">
        <h2 class="sg-card-header-title" style="font-size:1.1rem;">
            @if($editor)
                <i class="fas fa-user-edit mr-1"></i> {!! __('editor.production_by', ['name' => '<strong>'.e($editor->name).'</strong>']) !!}
                &nbsp;<small class="text-muted">{{ $from->format('d/m/Y') }} — {{ $to->format('d/m/Y') }}</small>
            @else
                <i class="fas fa-users mr-1"></i> {{ __('editor.production_aggregate') }}
                &nbsp;<small class="text-muted">{{ $from->format('d/m/Y') }} — {{ $to->format('d/m/Y') }}</small>
            @endif
        </h2>
    </div>

    {{-- KPI --}}
    <div class="row sg-grid-row">

        <div class="col-lg-3 col-6 sg-grid-col">
            <div class="sg-stat-card">
                <div class="sg-stat-icon grad-green"><i class="fas fa-plus-circle"></i></div>
                <div>
                    <div class="sg-stat-value">{{ $productionMetrics['questions_created'] }}</div>
                    <div class="sg-stat-label">{{ __('editor.kpi_questions_created') }}</div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6 sg-grid-col">
            <div class="sg-stat-card">
                <div class="sg-stat-icon grad-orange"><i class="fas fa-edit"></i></div>
                <div>
                    <div class="sg-stat-value">{{ $productionMetrics['questions_updated'] }}</div>
                    <div class="sg-stat-label">{{ __('editor.kpi_questions_updated') }}</div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6 sg-grid-col">
            <div class="sg-stat-card">
                <div class="sg-stat-icon grad-blue"><i class="fas fa-paper-plane"></i></div>
                <div>
                    <div class="sg-stat-value">{{ $productionMetrics['quizzes_published'] }}</div>
                    <div class="sg-stat-label">{{ __('editor.kpi_quizzes_published') }}</div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6 sg-grid-col">
            <div class="sg-stat-card">
                <div class="sg-stat-icon grad-red"><i class="fas fa-check-double"></i></div>
                <div>
                    <div class="sg-stat-value">{{ $productionMetrics['quizzes_confirmed'] }}</div>
                    <div class="sg-stat-label">{{ __('editor.kpi_quizzes_confirmed') }}</div>
                </div>
            </div>
        </div>

    </div>

    {{-- GRAFICO TREND --}}
    <div class="row sg-grid-row">
        <div class="col-12 sg-grid-col">
            <div class="sg-card">
                <div class="sg-card-header">
                    <h2 class="sg-card-header-title">{{ __('editor.chart_activity') }}</h2>
                </div>
                <div class="sg-card-body">
                    @if(collect($productionMetrics['activity_by_day'])->sum('total') === 0)
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-chart-line fa-3x mb-3"></i>
                        <p>{{ __('editor.chart_no_activity') }}</p>
                    </div>
                    @else
                    <canvas id="activityChart" style="max-height:280px;"></canvas>
                    @endif
                </div>
            </div>
        </div>
    </div>


</div>
@endsection

@section('js')
@parent

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
(function () {
    // ── TREND ATTIVITÀ ────────────────────────────────────────────────────────
    var activityCanvas = document.getElementById('activityChart');
    if (activityCanvas) {
        var activityData = @json($productionMetrics['activity_by_day']);
        new Chart(activityCanvas, {
            type: 'line',
            data: {
                labels: activityData.map(function (i) { return i.date; }),
                datasets: [{
                    label: '{{ __('editor.chart_activity_label') }}',
                    data: activityData.map(function (i) { return i.total; }),
                    tension: 0.35,
                    borderColor: '#28a745',
                    backgroundColor: '#28a74522',
                    fill: true,
                    pointRadius: 3,
                    pointBackgroundColor: '#28a745',
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
                    x: {
                        grid: { display: false },
                        ticks: { maxTicksLimit: 15 }
                    }
                }
            }
        });
    }


    // ── PERIODO RAPIDO ────────────────────────────────────────────────────────
    function pad(n) { return String(n).padStart(2, '0'); }
    function fmt(d) {
        return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
    }
    function startOfQuarter(d) {
        var m = Math.floor(d.getMonth() / 3) * 3;
        return new Date(d.getFullYear(), m, 1);
    }

    var presets = {
        month: function () {
            var n = new Date();
            return { from: new Date(n.getFullYear(), n.getMonth(), 1), to: n };
        },
        quarter: function () {
            var n = new Date();
            return { from: startOfQuarter(n), to: n };
        },
        year: function () {
            var n = new Date();
            return { from: new Date(n.getFullYear(), 0, 1), to: n };
        },
    };

    document.querySelectorAll('[data-preset]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var p = presets[btn.dataset.preset]();
            document.getElementById('from').value = fmt(p.from);
            document.getElementById('to').value   = fmt(p.to);
            document.getElementById('filter-form').submit();
        });
    });
})();
</script>

@endsection
