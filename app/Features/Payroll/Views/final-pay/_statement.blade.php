@php
    $money = fn (string $amount) => number_format((float) $amount, 2);
    $lines = collect($finalPay->lines ?? []);
@endphp
<table class="payslip-lines" width="100%">
    <thead><tr><th colspan="3">Earnings</th></tr></thead>
    <tbody>
        @foreach ($lines->where('kind', 'earning') as $line)
            <tr><td>{{ $line['label'] }}</td><td class="qty">{{ $line['quantity'] !== null ? rtrim(rtrim(number_format((float) $line['quantity'], 2), '0'), '.').' '.$line['unit'] : '' }}</td><td class="amount">{{ $money($line['amount']) }}</td></tr>
        @endforeach
        <tr class="total"><td colspan="2">Total earnings</td><td class="amount">{{ $finalPay->total_earnings->format(false) }}</td></tr>
    </tbody>
    <thead><tr><th colspan="3">Deductions</th></tr></thead>
    <tbody>
        @forelse ($lines->where('kind', 'deduction') as $line)
            <tr><td>{{ $line['label'] }}</td><td></td><td class="amount">{{ $money($line['amount']) }}</td></tr>
        @empty
            <tr><td colspan="3">None</td></tr>
        @endforelse
        <tr class="total"><td colspan="2">Total deductions</td><td class="amount">{{ $finalPay->total_deductions->format(false) }}</td></tr>
    </tbody>
    <tbody>
        <tr class="net"><td colspan="2">NET FINAL PAY</td><td class="amount">{{ $finalPay->net_pay->format() }}</td></tr>
    </tbody>
</table>

@if ($finalPay->details)
    <table class="payslip-lines small" width="100%">
        <thead><tr><th colspan="2">Basis</th></tr></thead>
        <tbody>
            <tr><td>Daily rate</td><td class="amount">{{ $money($finalPay->details['daily_rate']) }}</td></tr>
            <tr><td>Basic salary earned this year (finalized payroll)</td><td class="amount">{{ $money($finalPay->details['basic_salary_this_year']) }}</td></tr>
            <tr><td>13th month due / already paid</td><td class="amount">{{ $money($finalPay->details['thirteenth_month_due']) }} / {{ $money($finalPay->details['thirteenth_month_paid']) }}</td></tr>
            <tr><td>Unused convertible leave (days)</td><td class="amount">{{ $finalPay->details['unused_leave_days'] }}</td></tr>
            <tr><td>Annual taxable income / tax due</td><td class="amount">{{ $money($finalPay->details['annual_taxable']) }} / {{ $money($finalPay->details['annual_tax_due']) }}</td></tr>
            <tr><td>Tax withheld before separation</td><td class="amount">{{ $money($finalPay->details['tax_withheld_to_date']) }}</td></tr>
        </tbody>
    </table>
@endif
