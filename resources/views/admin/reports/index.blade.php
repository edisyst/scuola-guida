@extends('layouts.admin')

@section('title', __('reports.title'))
@section('content_header')@endsection

@section('content')
<div class="sg-wrapper-fluid">

    <div class="sg-header">
        <p class="sg-header-subtitle">{{ __('reports.subtitle') }}</p>
        <h1 class="sg-header-title"><i class="fas fa-chart-pie mr-2"></i> {{ __('reports.title') }}</h1>
    </div>

    <div class="sg-card">
        <div class="sg-card-body">
            <form method="GET" action="{{ route('admin.reports.show') }}" id="report-form">

                <div class="form-group">
                    <label class="font-weight-bold">{{ __('reports.label_period') }}</label>
                    <div class="mt-1">
                        <button type="button" class="sg-btn sg-btn-light sg-btn-sm mr-1 mb-1" data-preset="current_month">{{ __('reports.preset_current_month') }}</button>
                        <button type="button" class="sg-btn sg-btn-light sg-btn-sm mr-1 mb-1" data-preset="last_month">{{ __('reports.preset_last_month') }}</button>
                        <button type="button" class="sg-btn sg-btn-light sg-btn-sm mr-1 mb-1" data-preset="current_quarter">{{ __('reports.preset_current_quarter') }}</button>
                        <button type="button" class="sg-btn sg-btn-light sg-btn-sm mr-1 mb-1" data-preset="last_quarter">{{ __('reports.preset_last_quarter') }}</button>
                        <button type="button" class="sg-btn sg-btn-light sg-btn-sm mr-1 mb-1" data-preset="current_year">{{ __('reports.preset_current_year') }}</button>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="from">{{ __('reports.label_from') }}</label>
                            <input type="date" name="from" id="from" class="form-control"
                                   value="{{ $defaultFrom }}" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="to">{{ __('reports.label_to') }}</label>
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
                            {{ __('reports.label_compare') }}
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="license_type_id">{{ __('reports.filter_license_type') }}</label>
                    <select name="license_type_id" id="license_type_id" class="form-control">
                        <option value="">{{ __('reports.filter_license_type_all') }}</option>
                        @foreach($licenseTypes as $lt)
                            <option value="{{ $lt->id }}">{{ $lt->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <button type="submit" class="sg-btn sg-btn-primary">
                        <i class="fas fa-chart-pie"></i> {{ __('reports.action_generate') }}
                    </button>
                    <button type="button" id="btn-export-pdf" class="sg-btn sg-btn-danger ml-2">
                        <i class="fas fa-file-pdf"></i> {{ __('reports.action_export_pdf') }}
                    </button>
                </div>
            </form>

            {{-- Form nascosto per l'export PDF --}}
            <form method="GET" action="{{ route('admin.reports.export-pdf') }}" id="pdf-form">
                <input type="hidden" id="pdf-from" name="from">
                <input type="hidden" id="pdf-to" name="to">
                <input type="hidden" id="pdf-compare" name="compare">
                <input type="hidden" id="pdf-license-type-id" name="license_type_id">
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
            document.getElementById('pdf-from').value           = document.getElementById('from').value;
            document.getElementById('pdf-to').value            = document.getElementById('to').value;
            document.getElementById('pdf-compare').value       = document.getElementById('compare').checked ? '1' : '';
            document.getElementById('pdf-license-type-id').value = document.getElementById('license_type_id').value;
            document.getElementById('pdf-form').submit();
        });
    })();
</script>
@endsection
