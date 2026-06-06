<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="utf-8">
<title>Report periodico</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #222; background: #fff; }

    /* Header */
    .doc-header { border-bottom: 2px solid #4361ee; padding-bottom: 8px; margin-bottom: 16px; }
    .doc-header table { width: 100%; }
    .doc-header .logo { font-size: 18px; font-weight: bold; color: #4361ee; }
    .doc-header .subtitle { font-size: 10px; color: #666; }
    .doc-header .meta { font-size: 9px; color: #888; text-align: right; }

    /* Section titles */
    h2 { font-size: 12px; color: #4361ee; border-bottom: 1px solid #e0e0e0; padding-bottom: 4px; margin: 14px 0 8px; }

    /* KPI boxes */
    .kpi-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    .kpi-table td { width: 25%; border: 1px solid #dee2e6; padding: 10px; text-align: center; }
    .kpi-value { font-size: 20px; font-weight: bold; color: #4361ee; }
    .kpi-label { font-size: 9px; color: #666; margin-top: 2px; }

    /* Delta */
    .delta-up   { color: #28a745; font-size: 9px; }
    .delta-down { color: #dc3545; font-size: 9px; }
    .delta-flat { color: #888;    font-size: 9px; }

    /* Data tables */
    .data-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    .data-table th { background: #f1f3f5; border: 1px solid #dee2e6; padding: 5px 8px; text-align: left; font-size: 9px; }
    .data-table td { border: 1px solid #dee2e6; padding: 4px 8px; font-size: 9px; }
    .data-table tr:nth-child(even) td { background: #f9f9f9; }
    .text-right { text-align: right; }
    .text-center { text-align: center; }
    .text-success { color: #28a745; }
    .text-danger  { color: #dc3545; }

    /* Comparison section */
    .cmp-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    .cmp-table th { background: #4361ee; color: #fff; padding: 5px 8px; font-size: 9px; border: 1px solid #3451cc; }
    .cmp-table td { border: 1px solid #dee2e6; padding: 4px 8px; font-size: 9px; }

    /* Footer */
    .doc-footer { border-top: 1px solid #dee2e6; margin-top: 20px; padding-top: 6px; font-size: 8px; color: #aaa; }
    .doc-footer table { width: 100%; }
</style>
</head>
<body>

{{-- HEADER --}}
<div class="doc-header">
    <table>
        <tr>
            <td>
                <div class="logo">ScuolaGUIDA</div>
                <div class="subtitle">Report periodico — {{ $from->format('d/m/Y') }} / {{ $to->format('d/m/Y') }}</div>
                @if($licenseType)
                    <div class="subtitle" style="margin-top:4px;">{{ __('reports.pdf_license_type') }}: {{ $licenseType->name }}</div>
                @else
                    <div class="subtitle" style="margin-top:4px;">{{ __('reports.pdf_all_license_types') }}</div>
                @endif
            </td>
            <td class="meta">
                Generato il {{ $generated_at->format('d/m/Y H:i') }}<br>
                @if($compare) Con confronto periodo precedente @endif
            </td>
        </tr>
    </table>
</div>

{{-- KPI METRICS --}}
<h2>Metriche chiave</h2>
@php $c = $current; @endphp

<table class="kpi-table">
    <tr>
        <td>
            <div class="kpi-value">{{ $c['total_attempts'] }}</div>
            <div class="kpi-label">Tentativi completati</div>
            @if($compare && isset($delta['total_attempts']))
                @php $dv = $delta['total_attempts']; @endphp
                <div class="{{ $dv > 0 ? 'delta-up' : ($dv < 0 ? 'delta-down' : 'delta-flat') }}">
                    {{ $dv > 0 ? '+' : '' }}{{ number_format($dv, 1) }}% vs prec.
                </div>
            @endif
        </td>
        <td>
            <div class="kpi-value">{{ $c['active_students'] }}</div>
            <div class="kpi-label">Studenti attivi</div>
            @if($compare && isset($delta['active_students']))
                @php $dv = $delta['active_students']; @endphp
                <div class="{{ $dv > 0 ? 'delta-up' : ($dv < 0 ? 'delta-down' : 'delta-flat') }}">
                    {{ $dv > 0 ? '+' : '' }}{{ number_format($dv, 1) }}% vs prec.
                </div>
            @endif
        </td>
        <td>
            <div class="kpi-value">{{ $c['pass_rate'] !== null ? number_format($c['pass_rate'], 1) . '%' : '—' }}</div>
            <div class="kpi-label">Tasso di promozione</div>
            @if($compare && isset($delta['pass_rate']))
                @php $dv = $delta['pass_rate']; @endphp
                <div class="{{ $dv > 0 ? 'delta-up' : ($dv < 0 ? 'delta-down' : 'delta-flat') }}">
                    {{ $dv > 0 ? '+' : '' }}{{ number_format($dv, 1) }}% vs prec.
                </div>
            @endif
        </td>
        <td>
            <div class="kpi-value">{{ $c['average_score'] !== null ? number_format($c['average_score'], 1) . '%' : '—' }}</div>
            <div class="kpi-label">Punteggio medio</div>
            @if($compare && isset($delta['average_score']))
                @php $dv = $delta['average_score']; @endphp
                <div class="{{ $dv > 0 ? 'delta-up' : ($dv < 0 ? 'delta-down' : 'delta-flat') }}">
                    {{ $dv > 0 ? '+' : '' }}{{ number_format($dv, 1) }}% vs prec.
                </div>
            @endif
        </td>
    </tr>
</table>

{{-- COMPARISON TABLE --}}
@if($compare && isset($previous))
<h2>Confronto periodi</h2>
<table class="cmp-table">
    <thead>
        <tr>
            <th>Metrica</th>
            <th class="text-center">{{ $from->format('d/m/Y') }} – {{ $to->format('d/m/Y') }}</th>
            <th class="text-center">{{ $period['prev_from']->format('d/m/Y') }} – {{ $period['prev_to']->format('d/m/Y') }}</th>
            <th class="text-center">Delta</th>
        </tr>
    </thead>
    <tbody>
        @foreach([
            ['Tentativi completati', $current['total_attempts'],  $previous['total_attempts'],  $delta['total_attempts'] ?? null, false],
            ['Studenti attivi',      $current['active_students'], $previous['active_students'], $delta['active_students'] ?? null, false],
            ['Tasso promozione',     $current['pass_rate'] !== null ? number_format($current['pass_rate'], 1).'%' : '—', $previous['pass_rate'] !== null ? number_format($previous['pass_rate'], 1).'%' : '—', $delta['pass_rate'] ?? null, true],
            ['Punteggio medio',      $current['average_score'] !== null ? number_format($current['average_score'], 1).'%' : '—', $previous['average_score'] !== null ? number_format($previous['average_score'], 1).'%' : '—', $delta['average_score'] ?? null, true],
        ] as [$label, $curr, $prev, $dv, $isPercent])
        <tr>
            <td>{{ $label }}</td>
            <td class="text-center"><strong>{{ $curr }}</strong></td>
            <td class="text-center">{{ $prev }}</td>
            <td class="text-center">
                @if($dv === null) —
                @else <span class="{{ $dv > 0 ? 'delta-up' : ($dv < 0 ? 'delta-down' : 'delta-flat') }}">{{ $dv > 0 ? '+' : '' }}{{ number_format($dv, 1) }}%</span>
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

{{-- OUTCOMES BY CATEGORY --}}
<h2>Distribuzione risposte per categoria</h2>
@if(count($c['outcomes_by_category']) > 0)
<table class="data-table">
    <thead>
        <tr>
            <th>Categoria</th>
            <th class="text-right">Corrette</th>
            <th class="text-right">Sbagliate</th>
            <th class="text-right">Totale</th>
            <th class="text-right">% Successo</th>
        </tr>
    </thead>
    <tbody>
        @foreach($c['outcomes_by_category'] as $cat)
            @php $tot = $cat['correct'] + $cat['incorrect']; @endphp
            <tr>
                <td>{{ $cat['name'] }}</td>
                <td class="text-right text-success">{{ number_format($cat['correct']) }}</td>
                <td class="text-right text-danger">{{ number_format($cat['incorrect']) }}</td>
                <td class="text-right">{{ number_format($tot) }}</td>
                <td class="text-right">{{ $tot > 0 ? number_format($cat['correct'] / $tot * 100, 1) . '%' : '—' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
@else
    <p style="color:#888; font-size:9px;">Nessun dato per il periodo selezionato.</p>
@endif

{{-- TOP FAILED QUESTIONS --}}
<h2>Top {{ count($c['most_failed_questions']) }} domande più sbagliate</h2>
@if(count($c['most_failed_questions']) > 0)
<table class="data-table">
    <thead>
        <tr>
            <th style="width:3%">#</th>
            <th>Domanda</th>
            <th style="width:20%">Categoria</th>
            <th class="text-right" style="width:8%">Errori</th>
        </tr>
    </thead>
    <tbody>
        @foreach($c['most_failed_questions'] as $i => $q)
            <tr>
                <td class="text-center">{{ $i + 1 }}</td>
                <td>{{ mb_strimwidth($q['question'], 0, 140, '…') }}</td>
                <td>{{ $q['category'] }}</td>
                <td class="text-right text-danger">{{ number_format($q['errors']) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
@else
    <p style="color:#888; font-size:9px;">Nessuna domanda sbagliata nel periodo selezionato.</p>
@endif

{{-- FOOTER --}}
<div class="doc-footer">
    <table>
        <tr>
            <td>Documento generato automaticamente da ScuolaGUIDA</td>
            <td class="text-right">{{ $generated_at->format('d/m/Y H:i:s') }}</td>
        </tr>
    </table>
</div>

</body>
</html>
