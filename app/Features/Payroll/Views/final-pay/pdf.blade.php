<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Final pay {{ $finalPay->employee->employee_no }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #222; }
        h1 { font-size: 14px; margin: 0 0 4px; }
        .payslip-lines { margin-top: 10px; border-collapse: collapse; }
        .payslip-lines th { background: #eee; text-align: left; padding: 3px 4px; text-transform: uppercase; font-size: 9px; }
        .payslip-lines td { padding: 2px 4px; border-bottom: 1px solid #ddd; }
        .amount { text-align: right; white-space: nowrap; }
        .qty { text-align: right; color: #666; }
        .total td { font-weight: bold; }
        .net td { font-weight: bold; font-size: 12px; border-bottom: 0; }
        .small { font-size: 9px; }
    </style>
</head>
<body>
    <h1>{{ config('app.name') }} — Final pay statement</h1>
    <div>{{ $finalPay->employee->full_name }} · {{ $finalPay->employee->employee_no }} · separated {{ $finalPay->separation_date->format('M j, Y') }}</div>
    @include('payroll::final-pay._statement')
    <p class="small" style="margin-top:30px">Received by: ______________________________ Date: ______________</p>
</body>
</html>
