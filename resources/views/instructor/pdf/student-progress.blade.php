<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="utf-8">
<title>Progressi studente — {{ $student['name'] }}</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #222; background: #fff; }

    .doc-header { border-bottom: 2px solid #4361ee; padding-bottom: 8px; margin-bottom: 16px; }
    .doc-header table { width: 100%; }
    .doc-header .logo { font-size: 18px; font-weight: bold; color: #4361ee; }
    .doc-header .subtitle { font-size: 10px; color: #666; }
    .doc-header .meta { font-size: 9px; color: #888; text-align: right; }

    h2 { font-size: 12px; color: #4361ee; border-bottom: 1px solid #e0e0e0; padding-bottom: 4px; margin: 14px 0 8px; }

    .kpi-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    .kpi-table td { width: 25%; border: 1px solid #dee2e6; padding: 10px; text-align: center; }
    .kpi-value { font-size: 20px; font-weight: bold; color: #4361ee; }
    .kpi-label { font-size: 9px; color: #666; margin-top: 2px; }

    .data-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    .data-table th { background: #f1f3f5; border: 1px solid #dee2e6; padding: 5px 8px; text-align: left; font-size: 9px; }
    .data-table td { border: 1px solid #dee2e6; padding: 4px 8px; font-size: 9px; }
    .data-table tr:nth-child(even) td { background: #f9f9f9; }
    .text-right { text-align: right; }
    .text-center { text-align: center; }
    .text-success { color: #28a745; }
    .text-danger  { color: #dc3545; }

    .note-block { border: 1px solid #dee2e6; padding: 8px; margin-bottom: 8px; background: #f9f9f9; }
    .note-date { font-size: 8px; color: #888; margin-bottom: 3px; }
    .note-body { font-size: 9px; color: #333; }
    .no-data { color: #888; font-size: 9px; font-style: italic; }

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
                <div class="subtitle">Progressi studente — {{ $student['name'] }}</div>
            </td>
            <td class="meta">
                Istruttore: {{ $instructor['name'] }}<br>
                Esportato il {{ $generated_at->format('d/m/Y H:i') }}
            </td>
        </tr>
    </table>
</div>

{{-- KPI --}}
<h2>Riepilogo KPI</h2>
<table class="kpi-table">
    <tr>
        <td>
            <div class="kpi-value">{{ $stats['total_attempts'] }}</div>
            <div class="kpi-label">Tentativi totali</div>
        </td>
        <td>
            <div class="kpi-value">{{ $stats['pass_rate'] }}%</div>
            <div class="kpi-label">Tasso superamento</div>
        </td>
        <td>
            <div class="kpi-value">{{ $streak['current'] }}</div>
            <div class="kpi-label">Streak attuale</div>
        </td>
        <td>
            <div class="kpi-value">{{ count($badges) }}</div>
            <div class="kpi-label">Badge guadagnati</div>
        </td>
    </tr>
</table>

{{-- STATISTICHE DETTAGLIO --}}
<h2>Statistiche dettagliate</h2>
<table class="data-table" style="width:50%">
    <tbody>
        <tr><th>Domande risposte</th><td>{{ $stats['total_questions'] }}</td></tr>
        <tr><th>Risposte corrette</th><td>{{ $stats['total_correct'] }}</td></tr>
        <tr><th>Media percentuale</th><td>{{ $stats['avg_percentage'] }}%</td></tr>
        <tr><th>Migliore risultato</th><td>{{ $stats['best_percentage'] }}%</td></tr>
        <tr><th>Superati / Falliti</th><td>{{ $stats['passed_count'] }} / {{ $stats['failed_count'] }}</td></tr>
        <tr><th>Streak più lungo</th><td>{{ $streak['longest'] }} giorni</td></tr>
    </tbody>
</table>

{{-- ULTIMI TENTATIVI --}}
<h2>Ultimi tentativi</h2>
@if(empty($stats['latest_attempts']))
    <p class="no-data">Nessun tentativo registrato.</p>
@else
<table class="data-table">
    <thead>
        <tr>
            <th>Quiz</th>
            <th class="text-right" style="width:12%">Punteggio</th>
            <th class="text-right" style="width:8%">%</th>
            <th class="text-center" style="width:15%">Esito</th>
            <th class="text-right" style="width:18%">Data</th>
        </tr>
    </thead>
    <tbody>
        @foreach($stats['latest_attempts'] as $attempt)
        <tr>
            <td>{{ mb_strimwidth($attempt['quiz_title'], 0, 60, '…') }}</td>
            <td class="text-right">{{ $attempt['score'] }}/{{ $attempt['total_questions'] }}</td>
            <td class="text-right">{{ $attempt['percentage'] }}%</td>
            <td class="text-center {{ $attempt['is_passed'] ? 'text-success' : 'text-danger' }}">
                {{ $attempt['is_passed'] ? 'Superato' : 'Non superato' }}
            </td>
            <td class="text-right">
                {{ $attempt['created_at'] ? \Carbon\Carbon::parse($attempt['created_at'])->format('d/m/Y H:i') : '—' }}
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

{{-- STATISTICHE PER QUIZ --}}
@if(!empty($stats['avg_by_quiz']))
<h2>Statistiche per quiz</h2>
<table class="data-table">
    <thead>
        <tr>
            <th>Quiz</th>
            <th class="text-right" style="width:12%">Tentativi</th>
            <th class="text-right" style="width:12%">Media %</th>
            <th class="text-right" style="width:12%">Migliore %</th>
        </tr>
    </thead>
    <tbody>
        @foreach($stats['avg_by_quiz'] as $quiz)
        <tr>
            <td>{{ mb_strimwidth($quiz['title'], 0, 60, '…') }}</td>
            <td class="text-right">{{ $quiz['attempts'] }}</td>
            <td class="text-right">{{ $quiz['avg_pct'] }}%</td>
            <td class="text-right">{{ $quiz['best_pct'] }}%</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

{{-- BADGE --}}
@if(!empty($badges))
<h2>Badge guadagnati</h2>
<table class="data-table" style="width:60%">
    <thead>
        <tr>
            <th>Codice badge</th>
            <th class="text-right" style="width:35%">Conseguito il</th>
        </tr>
    </thead>
    <tbody>
        @foreach($badges as $badge)
        <tr>
            <td>{{ $badge['badge_code'] }}</td>
            <td class="text-right">
                {{ $badge['earned_at'] ? \Carbon\Carbon::parse($badge['earned_at'])->format('d/m/Y') : '—' }}
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

{{-- NOTE ISTRUTTORE --}}
<h2>Note dell'istruttore</h2>
@if(empty($notes))
    <p class="no-data">Nessuna nota registrata.</p>
@else
    @foreach($notes as $note)
    <div class="note-block">
        <div class="note-date">
            {{ $note['created_at'] ? \Carbon\Carbon::parse($note['created_at'])->format('d/m/Y H:i') : '—' }}
        </div>
        <div class="note-body">{{ $note['body'] }}</div>
    </div>
    @endforeach
@endif

{{-- FOOTER --}}
<div class="doc-footer">
    <table>
        <tr>
            <td>Documento generato da ScuolaGUIDA — istruttore: {{ $instructor['name'] }}</td>
            <td class="text-right">{{ $generated_at->format('d/m/Y H:i:s') }}</td>
        </tr>
    </table>
</div>

</body>
</html>
