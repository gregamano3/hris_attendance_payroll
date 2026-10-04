@php use App\Shared\Format; @endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>DTR {{ $employee->employee_no }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #111; }
        .center { text-align: center; }
        h1 { font-size: 13px; margin: 2px 0; letter-spacing: 1px; }
        .form-no { font-size: 8px; font-style: italic; }
        table.dtr { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.dtr th, table.dtr td { border: 1px solid #333; padding: 2px 3px; text-align: center; }
        table.dtr th { background: #eee; font-size: 8px; }
        .muted { color: #555; }
        .rest { background: #f3f3f3; }
        .meta td { padding: 1px 0; }
        .sign { margin-top: 22px; width: 100%; }
        .sign td { width: 50%; text-align: center; padding-top: 24px; }
        .line { border-top: 1px solid #111; display: inline-block; width: 80%; padding-top: 2px; }
    </style>
</head>
<body>
    <div class="form-no">Civil Service Form No. 48 (format)</div>
    <div class="center">
        <h1>DAILY TIME RECORD</h1>
        <div><strong>{{ strtoupper($employee->first_name.' '.($employee->middle_name ? mb_substr($employee->middle_name, 0, 1).'. ' : '').$employee->last_name) }}</strong></div>
        <div class="muted">(Name)</div>
    </div>

    <table class="meta" width="100%">
        <tr><td>For the period: <strong>{{ $period->label() }}</strong></td><td style="text-align:right">Employee no.: {{ $employee->employee_no }}</td></tr>
        <tr><td>Office: {{ $employee->department->name ?? '—' }}</td><td style="text-align:right">Position: {{ $employee->position->title ?? '—' }}</td></tr>
        <tr><td colspan="2">Official hours: {{ $days->first()?->shift?->label() ?? 'Per assigned shift' }}</td></tr>
    </table>

    <table class="dtr">
        <thead>
            <tr>
                <th rowspan="2">Day</th>
                <th colspan="2">A.M.</th>
                <th colspan="2">P.M.</th>
                <th colspan="2">Late / Undertime</th>
                <th rowspan="2">OT</th>
                <th rowspan="2">Remarks</th>
            </tr>
            <tr><th>Arrival</th><th>Departure</th><th>Arrival</th><th>Departure</th><th>Hours</th><th>Minutes</th></tr>
        </thead>
        <tbody>
            @foreach ($days as $day)
                @php
                    $tardy = $day->late_minutes + $day->undertime_minutes;
                    $remarks = match (true) {
                        $day->holiday_type !== null && $day->status->value !== 'present' => $day->holiday_type->label(),
                        $day->is_rest_day && $day->status->value !== 'present' => 'Rest day',
                        $day->leaveRequest !== null => $day->leaveRequest->leaveType->code.((float) $day->leave_fraction === 0.5 ? ' (½)' : ''),
                        in_array($day->status->value, ['absent', 'incomplete'], true) => $day->status->label(),
                        default => '',
                    };
                    $inAm = $day->time_in && $day->time_in->hour < 12;
                    $outAm = $day->time_out && $day->time_out->hour < 12;
                @endphp
                <tr @class(['rest' => $day->is_rest_day || $day->holiday_type])>
                    <td>{{ $day->date->format('j') }} <span class="muted">{{ $day->date->format('D') }}</span></td>
                    <td>{{ $inAm ? $day->time_in->format('g:i') : '' }}</td>
                    <td>{{ $outAm ? $day->time_out->format('g:i') : '' }}</td>
                    <td>{{ $day->time_in && ! $inAm ? $day->time_in->format('g:i') : '' }}</td>
                    <td>{{ $day->time_out && ! $outAm ? $day->time_out->format('g:i') : '' }}</td>
                    <td>{{ $tardy ? intdiv($tardy, 60) : '' }}</td>
                    <td>{{ $tardy ? $tardy % 60 : '' }}</td>
                    <td>{{ $day->overtime_minutes ? Format::minutes($day->overtime_minutes) : '' }}</td>
                    <td>{{ $remarks }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="5" style="text-align:right">TOTAL</th>
                <th>{{ intdiv($totals['late_minutes'] + $totals['undertime_minutes'], 60) }}</th>
                <th>{{ ($totals['late_minutes'] + $totals['undertime_minutes']) % 60 }}</th>
                <th>{{ Format::minutes((int) $days->sum('overtime_minutes')) }}</th>
                <th>{{ $totals['days_present'] }} present · {{ $totals['days_absent'] }} absent</th>
            </tr>
        </tfoot>
    </table>

    <p style="margin-top:10px">
        I certify on my honor that the above is a true and correct report of the hours of work performed, record of which was made
        daily at the time of arrival and departure from office.
    </p>

    <table class="sign">
        <tr>
            <td><span class="line">Employee's signature</span></td>
            <td><span class="line">Verified as to the prescribed office hours — In charge</span></td>
        </tr>
    </table>
    <p class="muted" style="margin-top:12px">Generated {{ now()->format('M j, Y g:i A') }} by {{ config('app.name') }}.</p>
</body>
</html>
