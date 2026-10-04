{{-- Shared by the HTML and PDF payslip. Uses only basic table markup. --}}
@php
    $earnings = $payslip->lines->where('kind', 'earning');
    $deductions = $payslip->lines->where('kind', 'deduction');
    $employer = $payslip->lines->where('kind', 'employer');
    $qty = fn ($line) => $line->quantity !== null ? rtrim(rtrim(number_format((float) $line->quantity, 2), '0'), '.').' '.$line->unit : '';
@endphp

<table class="payslip-meta" width="100%">
    <tr>
        <td><strong>{{ $payslip->employee_name }}</strong><br>{{ $payslip->employee_no }}<br>{{ $payslip->position }} · {{ $payslip->department }}</td>
        <td style="text-align:right">
            Period: <strong>{{ $payslip->run->period()->label() }}</strong><br>
            Pay date: {{ $payslip->run->pay_date->format('M j, Y') }}<br>
            Rate: {{ $payslip->basic_rate->format() }} / {{ $payslip->rate_type === 'monthly' ? 'month' : 'day' }}
            (daily {{ $payslip->daily_rate->format() }})
        </td>
    </tr>
</table>

<table class="payslip-lines" width="100%">
    <thead><tr><th colspan="3">Earnings</th></tr></thead>
    <tbody>
        @foreach ($earnings as $line)
            <tr><td>{{ $line->label }}</td><td class="qty">{{ $qty($line) }}</td><td class="amount">{{ $line->amount->format(false) }}</td></tr>
        @endforeach
        <tr class="total"><td colspan="2">Gross pay</td><td class="amount">{{ $payslip->gross_pay->format(false) }}</td></tr>
    </tbody>
    <thead><tr><th colspan="3">Deductions</th></tr></thead>
    <tbody>
        @foreach ($deductions as $line)
            <tr><td>{{ $line->label }}</td><td class="qty"></td><td class="amount">{{ $line->amount->format(false) }}</td></tr>
        @endforeach
        <tr class="total"><td colspan="2">Total deductions</td><td class="amount">{{ $payslip->total_deductions->format(false) }}</td></tr>
    </tbody>
    <tbody>
        <tr class="net"><td colspan="2">NET PAY</td><td class="amount">{{ $payslip->net_pay->format() }}</td></tr>
    </tbody>
</table>

<table class="payslip-lines small" width="100%">
    <thead><tr><th colspan="2">Employer contributions (not deducted)</th></tr></thead>
    <tbody>
        @foreach ($employer as $line)
            <tr><td>{{ $line->label }}</td><td class="amount">{{ $line->amount->format(false) }}</td></tr>
        @endforeach
    </tbody>
    <thead><tr><th colspan="2">Attendance</th></tr></thead>
    <tbody>
        <tr><td>Days worked / absent / paid leave</td><td class="amount">{{ $payslip->attendance['days_worked'] ?? 0 }} / {{ $payslip->attendance['days_absent'] ?? 0 }} / {{ $payslip->attendance['paid_leave_days'] ?? 0 }}</td></tr>
        <tr><td>Late / undertime / overtime (minutes)</td><td class="amount">{{ $payslip->attendance['late_minutes'] ?? 0 }} / {{ $payslip->attendance['undertime_minutes'] ?? 0 }} / {{ $payslip->attendance['overtime_minutes'] ?? 0 }}</td></tr>
        <tr><td>Taxable income</td><td class="amount">{{ $payslip->taxable_income->format(false) }}</td></tr>
    </tbody>
</table>
