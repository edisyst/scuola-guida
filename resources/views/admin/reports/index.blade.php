@extends('layouts.admin')

@section('title', 'Report periodici')
@section('content_header')@endsection

@section('content')
<div class="sg-wrapper">

    <div class="sg-header">
        <p class="sg-header-subtitle">Amministrazione</p>
        <h1 class="sg-header-title"><i class="fas fa-chart-pie mr-2"></i> Report periodici</h1>
    </div>

    <div class="sg-card">
        <div class="sg-card-body">
            <form method="GET" action="{{ route('admin.reports.show') }}" id="report-form">

                <div class="form-group">
                    <label class="font-weight-bold">Periodo rapido</label>
                    <div class="mt-1">
                        <button type="button" class="sg-btn sg-btn-light sg-btn-sm mr-1 mb-1" data-preset="current_month">Mese corrente</button>
                        <button type="button" class="sg-btn sg-btn-light sg-btn-sm mr-1 mb-1" data-preset="last_month">Mese scorso</button>
                        <button type="button" class="sg-btn sg-btn-light sg-btn-sm mr-1 mb-1" data-preset="current_quarter">Trimestre corrente</button>
                        <button type="button" class="sg-btn sg-btn-light sg-btn-sm mr-1 mb-1" data-preset="last_quarter">Trimestre scorso</button>
                        <button type="button" class="sg-btn sg-btn-light sg-btn-sm mr-1 mb-1" data-preset="current_year">Anno corrente</button>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="from">Data inizio</label>
                            <input type="date" name="from" id="from" class="form-control"
                                   value="{{ $defaultFrom }}" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="to">Data fine</label>
                            <input type="date" name="to" id="to" class="form-control"
                                   value="{{ $defaultTo }}" required>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="compare"
                               name="compare" value="1">
                        <label class="custom-control-label" for="compare">
                            Confronta con il periodo precedente di pari durata
                        </label>
                    </div>
                </div>

                <div>
                    <button type="submit" class="sg-btn sg-btn-primary">
                        <i class="fas fa-chart-pie"></i> Genera report
                    </button>
                    <button type="button" id="btn-export-pdf" class="sg-btn sg-btn-danger ml-2">
                        <i class="fas fa-file-pdf"></i> Esporta PDF
                    </button>
                </div>
            </form>

            {{-- Form nascosto per l'export PDF --}}
            <form method="GET" action="{{ route('admin.reports.export-pdf') }}" id="pdf-form">
                <input type="hidden" id="pdf-from" name="from">
                <input type="hidden" id="pdf-to" name="to">
                <input type="hidden" id="pdf-compare" name="compare">
            </form>
        </div>
    </div>

</div>
@endsection

@section('js')
@parent
<script>
    (function () {
        function pad(n) { return String(n).padStart(2, '0'); }

        function fmt(d) {
            return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
        }

        function startOfQuarter(d) {
            var m = Math.floor(d.getMonth() / 3) * 3;
            return new Date(d.getFullYear(), m, 1);
        }

        var presets = {
            current_month: function () {
                var n = new Date();
                return { from: new Date(n.getFullYear(), n.getMonth(), 1),
                         to:   new Date(n.getFullYear(), n.getMonth() + 1, 0) };
            },
            last_month: function () {
                var n = new Date();
                return { from: new Date(n.getFullYear(), n.getMonth() - 1, 1),
                         to:   new Date(n.getFullYear(), n.getMonth(), 0) };
            },
            current_quarter: function () {
                var n = new Date();
                var s = startOfQuarter(n);
                return { from: s,
                         to:   new Date(s.getFullYear(), s.getMonth() + 3, 0) };
            },
            last_quarter: function () {
                var n = new Date();
                var s = startOfQuarter(new Date(n.getFullYear(), n.getMonth() - 3, 1));
                return { from: s,
                         to:   new Date(s.getFullYear(), s.getMonth() + 3, 0) };
            },
            current_year: function () {
                var y = new Date().getFullYear();
                return { from: new Date(y, 0, 1), to: new Date(y, 11, 31) };
            },
        };

        document.querySelectorAll('[data-preset]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var p = presets[btn.dataset.preset]();
                document.getElementById('from').value = fmt(p.from);
                document.getElementById('to').value   = fmt(p.to);
            });
        });

        document.getElementById('btn-export-pdf').addEventListener('click', function () {
            document.getElementById('pdf-from').value    = document.getElementById('from').value;
            document.getElementById('pdf-to').value      = document.getElementById('to').value;
            document.getElementById('pdf-compare').value = document.getElementById('compare').checked ? '1' : '';
            document.getElementById('pdf-form').submit();
        });
    })();
</script>
@endsection
