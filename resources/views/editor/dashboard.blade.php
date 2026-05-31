@extends('layouts.admin')

@section('title', 'Produzione contenuti')
@section('content_header')@endsection

@section('content')
<div class="sg-wrapper">

    <div class="sg-header">
        <p class="sg-header-subtitle">Editor</p>
        <h1 class="sg-header-title"><i class="fas fa-pen-fancy mr-2"></i> Produzione contenuti</h1>
    </div>

    {{-- FILTRI PERIODO + SELEZIONE EDITOR (admin) --}}
    <div class="sg-card mb-3">
        <div class="sg-card-body">
            <form method="GET" action="{{ route('editor.dashboard') }}" id="filter-form">

                @if($editors !== null)
                <div class="form-group mb-3">
                    <label class="font-weight-bold">Editor</label>
                    <select name="editor_id" id="editor_id" class="form-control" style="max-width:320px;">
                        <option value="">Tutti gli editor (aggregato)</option>
                        @foreach($editors as $e)
                        <option value="{{ $e->id }}" @selected($selectedEditorId == $e->id)>{{ $e->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div class="form-group mb-2">
                    <label class="font-weight-bold">Periodo rapido</label>
                    <div class="mt-1">
                        <button type="button" class="sg-btn sg-btn-light sg-btn-sm mr-1 mb-1" data-preset="month">Mese corrente</button>
                        <button type="button" class="sg-btn sg-btn-light sg-btn-sm mr-1 mb-1" data-preset="quarter">Trimestre corrente</button>
                        <button type="button" class="sg-btn sg-btn-light sg-btn-sm mr-1 mb-1" data-preset="year">Anno corrente</button>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="from">Da</label>
                            <input type="date" name="from" id="from" class="form-control"
                                   value="{{ $from->format('Y-m-d') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="to">A</label>
                            <input type="date" name="to" id="to" class="form-control"
                                   value="{{ $to->format('Y-m-d') }}">
                        </div>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <div class="form-group">
                            <button type="submit" class="sg-btn sg-btn-primary">
                                <i class="fas fa-search"></i> Visualizza
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
                <i class="fas fa-user-edit mr-1"></i> Produzione di <strong>{{ $editor->name }}</strong>
                &nbsp;<small class="text-muted">{{ $from->format('d/m/Y') }} — {{ $to->format('d/m/Y') }}</small>
            @else
                <i class="fas fa-users mr-1"></i> Produzione aggregata di tutti gli editor
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
                    <div class="sg-stat-label">Domande create</div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6 sg-grid-col">
            <div class="sg-stat-card">
                <div class="sg-stat-icon grad-orange"><i class="fas fa-edit"></i></div>
                <div>
                    <div class="sg-stat-value">{{ $productionMetrics['questions_updated'] }}</div>
                    <div class="sg-stat-label">Domande modificate</div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6 sg-grid-col">
            <div class="sg-stat-card">
                <div class="sg-stat-icon grad-blue"><i class="fas fa-paper-plane"></i></div>
                <div>
                    <div class="sg-stat-value">{{ $productionMetrics['quizzes_published'] }}</div>
                    <div class="sg-stat-label">Quiz pubblicati</div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6 sg-grid-col">
            <div class="sg-stat-card">
                <div class="sg-stat-icon grad-red"><i class="fas fa-check-double"></i></div>
                <div>
                    <div class="sg-stat-value">{{ $productionMetrics['quizzes_confirmed'] }}</div>
                    <div class="sg-stat-label">Quiz confermati</div>
                </div>
            </div>
        </div>

    </div>

    {{-- GRAFICO TREND --}}
    <div class="row sg-grid-row">
        <div class="col-12 sg-grid-col">
            <div class="sg-card">
                <div class="sg-card-header">
                    <h2 class="sg-card-header-title">Attività giornaliera</h2>
                </div>
                <div class="sg-card-body">
                    @if(collect($productionMetrics['activity_by_day'])->sum('total') === 0)
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-chart-line fa-3x mb-3"></i>
                        <p>Nessuna attività nel periodo selezionato.</p>
                    </div>
                    @else
                    <canvas id="activityChart" style="max-height:280px;"></canvas>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ── STATO DEI CONTENUTI ─────────────────────────────────────────────── --}}
    <div class="sg-header" style="margin-top:1.5rem;">
        <h2 class="sg-card-header-title" style="font-size:1.1rem;">
            <i class="fas fa-layer-group mr-1"></i> Stato dei contenuti
        </h2>
    </div>

    <div class="row sg-grid-row">

        {{-- Quiz per stato --}}
        @php $quizzesByState = $globalMetrics['quizzes_by_state']; @endphp
        <div class="col-lg-3 col-6 sg-grid-col">
            <div class="sg-stat-card">
                <div class="sg-stat-icon grad-blue"><i class="fas fa-clipboard-list"></i></div>
                <div>
                    <div class="sg-stat-value">{{ $quizzesByState['draft'] ?? 0 }}</div>
                    <div class="sg-stat-label">Quiz in bozza</div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6 sg-grid-col">
            <div class="sg-stat-card">
                <div class="sg-stat-icon grad-green"><i class="fas fa-paper-plane"></i></div>
                <div>
                    <div class="sg-stat-value">{{ $quizzesByState['published'] ?? 0 }}</div>
                    <div class="sg-stat-label">Quiz pubblicati</div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6 sg-grid-col">
            <div class="sg-stat-card">
                <div class="sg-stat-icon grad-orange"><i class="fas fa-trophy"></i></div>
                <div>
                    <div class="sg-stat-value">{{ $quizzesByState['confirmed'] ?? 0 }}</div>
                    <div class="sg-stat-label">Quiz confermati</div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6 sg-grid-col">
            <div class="sg-stat-card">
                <div class="sg-stat-icon grad-red"><i class="fas fa-image"></i></div>
                <div>
                    <div class="sg-stat-value">{{ $globalMetrics['questions_without_image'] }}</div>
                    <div class="sg-stat-label">
                        <a href="{{ route('admin.questions.index', ['no_image' => 1]) }}" class="text-inherit">
                            Domande senza immagine
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
                    <h2 class="sg-card-header-title">Domande per categoria</h2>
                </div>
                <div class="sg-card-body">
                    @if($globalMetrics['categories_by_question_count']->isEmpty())
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-tags fa-3x mb-2"></i>
                        <p>Nessuna categoria.</p>
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
                    <h2 class="sg-card-header-title">Domande più segnalate (aperte)</h2>
                </div>
                <div class="sg-card-body p-0">
                    @if($globalMetrics['most_reported_questions']->isEmpty())
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-flag fa-3x mb-2"></i>
                        <p>Nessuna segnalazione aperta.</p>
                    </div>
                    @else
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Domanda</th>
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
                    <h2 class="sg-card-header-title">Ultime segnalazioni da gestire</h2>
                </div>
                <div class="sg-card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Domanda</th>
                                    <th>Tipo</th>
                                    <th>Segnalato da</th>
                                    <th>Data</th>
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
                                            Gestisci
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
                        Vedi tutte le segnalazioni <i class="fas fa-arrow-right ml-1"></i>
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
                    label: 'Azioni',
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

    // ── CATEGORIE BAR ─────────────────────────────────────────────────────────
    var catCanvas = document.getElementById('categoriesChart');
    if (catCanvas) {
        var catData = @json($globalMetrics['categories_by_question_count']->take(15)->values());
        new Chart(catCanvas, {
            type: 'bar',
            data: {
                labels: catData.map(function (c) { return c.name; }),
                datasets: [{
                    label: 'Domande',
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
