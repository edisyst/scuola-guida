<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="utf-8">
<title>{{ __('driving.pdf_title') }}</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #222; background: #fff; }

    /* Header */
    .doc-header { border-bottom: 3px solid #4361ee; padding-bottom: 12px; margin-bottom: 16px; }
    .doc-header table { width: 100%; }
    .school-name { font-size: 16px; font-weight: bold; color: #4361ee; }
    .school-info { font-size: 9px; color: #666; line-height: 1.4; }
    .doc-title { font-size: 14px; font-weight: bold; color: #222; margin-top: 8px; }
    .doc-meta { font-size: 8px; color: #888; text-align: right; }

    /* Student info */
    .student-section { margin-bottom: 14px; }
    .student-section table { width: 100%; border: 1px solid #dee2e6; border-collapse: collapse; }
    .student-section td { padding: 4px 8px; font-size: 9px; border: 1px solid #dee2e6; }
    .student-label { background: #f1f3f5; font-weight: bold; width: 25%; }

    /* Section titles */
    h3 { font-size: 11px; color: #4361ee; border-bottom: 1px solid #e0e0e0; padding: 8px 0 4px 0; margin: 12px 0 8px; }

    /* Summary table */
    .summary-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
    .summary-table th { background: #4361ee; color: #fff; padding: 6px 8px; text-align: left; font-size: 9px; border: 1px solid #3451cc; }
    .summary-table td { padding: 5px 8px; border: 1px solid #dee2e6; font-size: 9px; }
    .summary-table tr:nth-child(even) td { background: #f9f9f9; }
    .text-right { text-align: right; }
    .text-center { text-align: center; }
    .badge-success { background: #d4edda; color: #155724; padding: 2px 4px; font-size: 8px; border-radius: 2px; }

    /* Sessions detail */
    .sessions-list { margin-bottom: 14px; }
    .session-module { background: #e8f0ff; padding: 6px 8px; margin: 8px 0 4px 0; font-weight: bold; font-size: 9px; }
    .sessions-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    .sessions-table th { background: #f1f3f5; padding: 4px 6px; text-align: left; font-size: 8px; border-bottom: 1px solid #dee2e6; }
    .sessions-table td { padding: 3px 6px; border-bottom: 1px solid #dee2e6; font-size: 8px; }
    .sessions-table tr:nth-child(even) { background: #fafafa; }

    /* Instructors */
    .instructors-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
    .instructors-table th { background: #4361ee; color: #fff; padding: 5px 8px; text-align: left; font-size: 9px; }
    .instructors-table td { padding: 4px 8px; border: 1px solid #dee2e6; font-size: 9px; }

    /* Footer */
    .doc-footer { border-top: 1px solid #dee2e6; margin-top: 20px; padding-top: 8px; }
    .footer-disclaimer { font-size: 8px; color: #666; line-height: 1.5; margin-bottom: 8px; }
    .footer-meta { font-size: 8px; color: #999; display: flex; justify-content: space-between; }
    .signature-area { margin-top: 12px; border-top: 1px solid #222; padding-top: 4px; font-size: 8px; }
</style>
</head>
<body>

{{-- HEADER --}}
<div class="doc-header">
    <table>
        <tr>
            <td style="width: 70%;">
                <div class="school-name">{{ $school['school_name'] }}</div>
                <div class="school-info">
                    @if($school['school_address'])
                        {{ $school['school_address'] }}<br>
                    @endif
                    @if($school['school_phone'])
                        Tel. {{ $school['school_phone'] }}<br>
                    @endif
                    @if($school['school_email'])
                        {{ $school['school_email'] }}<br>
                    @endif
                    @if($school['school_license'])
                        Aut. MIT n. {{ $school['school_license'] }}
                    @endif
                </div>
                <div class="doc-title" style="margin-top: 6px;">{{ __('driving.pdf_title') }}</div>
            </td>
            <td style="width: 30%;" class="doc-meta">
                {{ __('common.generated_on') }}<br>
                {{ $generated_at->format('d/m/Y H:i') }}
            </td>
        </tr>
    </table>
</div>

{{-- STUDENT INFO --}}
<div class="student-section">
    <table>
        <tr>
            <td class="student-label">{{ __('common.name') }}</td>
            <td>{{ $student['name'] }}</td>
        </tr>
        <tr>
            <td class="student-label">{{ __('common.email') }}</td>
            <td>{{ $student['email'] }}</td>
        </tr>
        @if($student['dob'])
            <tr>
                <td class="student-label">{{ __('common.date_of_birth') }}</td>
                <td>{{ $student['dob']->format('d/m/Y') }}</td>
            </tr>
        @endif
        <tr>
            <td class="student-label">{{ __('driving.license_type') }}</td>
            <td><strong>{{ $license_type }}</strong></td>
        </tr>
    </table>
</div>

{{-- PROGRESS SUMMARY --}}
<h3>{{ __('driving.pdf_progress_summary') }}</h3>
<table class="summary-table">
    <thead>
        <tr>
            <th>{{ __('driving.pdf_col_module') }}</th>
            <th class="text-right">{{ __('driving.pdf_col_required') }}</th>
            <th class="text-right">{{ __('driving.pdf_col_completed') }}</th>
            <th class="text-center">{{ __('driving.pdf_col_status') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach($progress['modules'] as $item)
            <tr>
                <td>{{ $item['module']->code }} — {{ $item['module']->name }}</td>
                <td class="text-right">{{ $item['required_hours'] }} h</td>
                <td class="text-right">{{ $item['completed_hours'] }} h</td>
                <td class="text-center">
                    @if($item['completed'])
                        <span class="badge-success">✓ {{ __('driving.pdf_completed') }}</span>
                    @else
                        —
                    @endif
                </td>
            </tr>
        @endforeach
        <tr style="background: #e8f0ff;">
            <td style="font-weight: bold;">{{ __('common.total') }}</td>
            <td class="text-right" style="font-weight: bold;">{{ $progress['total_required'] }} h</td>
            <td class="text-right" style="font-weight: bold;">{{ $progress['total_completed'] }} h</td>
            <td class="text-center" style="font-weight: bold;">{{ $progress['percentage'] }}%</td>
        </tr>
    </tbody>
</table>

{{-- CERTIFICATION STATUS --}}
<h3>{{ __('driving.pdf_cert_status') }}</h3>
@if($completion_status['all_completed'])
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 12px;">
        <tr>
            <td style="background: #d4edda; color: #155724; padding: 8px; font-weight: bold; font-size: 10px; border: 1px solid #c3e6cb;">
                ✓ {{ __('driving.pdf_cert_unlocked') }} — {{ $completion_status['completion_date']?->format('d/m/Y') ?? '—' }}
            </td>
        </tr>
        <tr>
            <td style="padding: 6px 8px; font-size: 9px; border: 1px solid #dee2e6;">
                {{ __('driving.pdf_cert_companion_desc') }}
            </td>
        </tr>
    </table>
@else
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 12px;">
        <tr>
            <td style="background: #fff3cd; color: #856404; padding: 8px; font-weight: bold; font-size: 10px; border: 1px solid #ffeaa7;">
                {{ __('driving.pdf_cert_in_progress') }} — {{ $completion_status['percentage'] }}%
            </td>
        </tr>
    </table>
@endif

{{-- SESSIONS DETAIL --}}
<h3>{{ __('driving.pdf_sessions_detail') }}</h3>
<div class="sessions-list">
    @forelse($progress['modules'] as $item)
        @php $modSessions = $sessions->get($item['module']->id, collect()); @endphp
        @if($modSessions->isNotEmpty())
            <div class="session-module">{{ $item['module']->code }} — {{ $item['module']->name }}</div>
            <table class="sessions-table">
                <thead>
                    <tr>
                        <th style="width: 15%;">{{ __('driving.pdf_session_date') }}</th>
                        <th style="width: 12%;">{{ __('driving.pdf_session_duration') }}</th>
                        <th style="width: 30%;">{{ __('driving.pdf_session_instructor') }}</th>
                        <th style="width: 43%;">{{ __('driving.pdf_session_notes') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($modSessions as $session)
                        <tr>
                            <td>{{ $session->conducted_at->format('d/m/Y') }}</td>
                            <td>{{ $session->duration_minutes }} min</td>
                            <td>
                                @if($session->instructor)
                                    {{ $session->instructor->name }}
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ Str::limit($session->notes, 80) ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @empty
        <p style="color: #999; font-size: 9px;">{{ __('driving.pdf_no_sessions') }}</p>
    @endforelse
</div>

{{-- INSTRUCTORS --}}
@if($instructors->isNotEmpty())
    <h3>{{ __('driving.pdf_instructors') }}</h3>
    <table class="instructors-table">
        <thead>
            <tr>
                <th style="width: 50%;">{{ __('common.name') }}</th>
                <th style="width: 50%;">{{ __('common.email') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($instructors as $instructor)
                <tr>
                    <td>{{ $instructor->name }}</td>
                    <td>{{ $instructor->email }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

{{-- FOOTER --}}
<div class="doc-footer">
    <div class="footer-disclaimer">
        <strong>{{ __('driving.pdf_disclaimer_title') }}</strong><br>
        {{ __('driving.pdf_disclaimer_text', ['school' => $school['school_name']]) }}
    </div>
    <div class="signature-area">
        {{ __('driving.pdf_signature_label') }}:
        <div style="margin-top: 20px; border-top: 1px solid #222; width: 40%; display: inline-block;"></div>
    </div>
    <div class="footer-meta" style="margin-top: 12px;">
        <span>{{ __('driving.pdf_generated_by', ['school' => $school['school_name']]) }}</span>
        <span>{{ $generated_at->format('d/m/Y H:i:s') }}</span>
    </div>
</div>

</body>
</html>
