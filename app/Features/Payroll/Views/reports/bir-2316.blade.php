@php use App\Features\Employees\Enums\GovernmentId; $e = $row['employee']; @endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>BIR Form 2316 {{ $year }} — {{ $e->employee_no }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #222; }
        h1 { font-size: 14px; margin: 0; }
        h2 { font-size: 11px; background: #eee; padding: 4px; margin: 14px 0 4px; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 3px 4px; border-bottom: 1px solid #ddd; }
        .amount { text-align: right; white-space: nowrap; }
        .total td { font-weight: bold; }
        .muted { color: #666; }
    </style>
</head>
<body>
    <h1>Certificate of Compensation Payment / Tax Withheld</h1>
    <div class="muted">BIR Form 2316 data · For the year {{ $year }} · {{ config('app.name') }}</div>

    <h2>Part I — Employee information</h2>
    <table>
        <tr><td>Name</td><td>{{ $e->last_name }}, {{ $e->first_name }} {{ $e->middle_name }}</td></tr>
        <tr><td>TIN</td><td>{{ GovernmentId::Tin->format($e->tin) ?: '—' }}</td></tr>
        <tr><td>Employee no.</td><td>{{ $e->employee_no }}</td></tr>
        <tr><td>Address</td><td>{{ $e->address ?? '—' }}</td></tr>
        <tr><td>Minimum wage earner</td><td>{{ $row['is_minimum_wage_earner'] ? 'Yes' : 'No' }}</td></tr>
    </table>

    <h2>Part II — Summary</h2>
    <table>
        <tr><td>Gross compensation income</td><td class="amount">{{ $row['gross']->format() }}</td></tr>
        <tr><td>Less: non-taxable / exempt compensation</td><td class="amount">{{ $row['non_taxable']->format() }}</td></tr>
        <tr class="total"><td>Taxable compensation income</td><td class="amount">{{ $row['taxable']->format() }}</td></tr>
        <tr><td>Tax due</td><td class="amount">{{ $row['tax_due']->format() }}</td></tr>
        <tr><td>Tax withheld</td><td class="amount">{{ $row['tax_withheld']->format() }}</td></tr>
        <tr class="total"><td>{{ $row['adjustment']->isNegative() ? 'Over-withheld (refund)' : 'Still to be withheld' }}</td><td class="amount">{{ $row['adjustment']->format() }}</td></tr>
    </table>

    <h2>Part III — Non-taxable / exempt compensation</h2>
    <table>
        <tr><td>Statutory minimum wage, holiday pay, overtime, night differential (MWE)</td><td class="amount">{{ $row['minimum_wage_exempt']->format() }}</td></tr>
        <tr><td>13th month pay and other benefits (up to the exempt ceiling)</td><td class="amount">{{ $row['thirteenth_month_exempt']->format() }}</td></tr>
        <tr><td>De minimis benefits and other non-taxable allowances</td><td class="amount">{{ $row['non_taxable_allowances']->format() }}</td></tr>
        <tr><td>SSS, PhilHealth, Pag-IBIG contributions (employee share)</td><td class="amount">{{ $row['contributions']->format() }}</td></tr>
    </table>

    <p class="muted" style="margin-top:16px">
        System-generated from finalized payroll. Transcribe to the official BIR Form 2316 for signing by the employer and the employee.
    </p>
</body>
</html>
