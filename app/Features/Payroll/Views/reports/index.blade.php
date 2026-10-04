@extends('layouts.app')

@section('title', 'Government reports')

@section('page')
    <div class="callout callout-info small">
        Reports only include <strong>finalized</strong> payroll runs. Monthly reports use runs whose period ends in the selected month.
        CSV files follow the column order of the official forms; check them against the agencies' latest upload formats before submitting.
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap gap-2 align-items-center">
            <h3 class="card-title me-auto">Monthly remittances — {{ $month->format('F Y') }}</h3>
            <form method="get" class="d-flex gap-2">
                <input type="month" name="month" value="{{ $month->format('Y-m') }}" class="form-control form-control-sm" aria-label="Month">
                <input type="hidden" name="year" value="{{ $year }}">
                <button class="btn btn-sm btn-outline-secondary">Show</button>
            </form>
        </div>
        <div class="card-body">
            <div class="row g-3">
                @foreach ([
                    ['sss', 'SSS contributions (R3)', $totals['SSS']->plus($totals['SSS_ER'])->plus($totals['SSS_EC']), 'EE '.$totals['SSS']->format().' · ER '.$totals['SSS_ER']->format().' · EC '.$totals['SSS_EC']->format()],
                    ['philhealth', 'PhilHealth premiums (RF-1)', $totals['PHILHEALTH']->plus($totals['PHILHEALTH_ER']), 'EE '.$totals['PHILHEALTH']->format().' · ER '.$totals['PHILHEALTH_ER']->format()],
                    ['pagibig', 'Pag-IBIG contributions (MCRF)', $totals['PAGIBIG']->plus($totals['PAGIBIG_ER']), 'EE '.$totals['PAGIBIG']->format().' · ER '.$totals['PAGIBIG_ER']->format()],
                    ['bir-1601c', 'BIR 1601-C withholding tax', $totals['TAX'], 'Gross '.$totals['gross']->format().' · taxable '.$totals['taxable']->format()],
                ] as [$agency, $label, $total, $detail])
                    <div class="col-md-6 col-xl-3">
                        <div class="border rounded p-3 h-100 d-flex flex-column">
                            <div class="fw-semibold">{{ $label }}</div>
                            <div class="fs-4 my-1">{{ $total->format() }}</div>
                            <div class="small text-body-secondary mb-2">{{ $detail }}</div>
                            <a href="{{ route('payroll.reports.contributions', ['agency' => $agency, 'month' => $month->format('Y-m')]) }}"
                                class="btn btn-sm btn-outline-primary mt-auto"><i class="bi bi-download me-1"></i> CSV</a>
                        </div>
                    </div>
                @endforeach
            </div>
            <p class="small text-body-secondary mt-3 mb-2">{{ $rows->count() }} employee(s) in finalized runs this month.</p>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <span class="small fw-semibold">Agency upload files:</span>
                @foreach (\App\Features\Payroll\EFiling\EFilingController::formats() as $format)
                    @if ($format->frequency() === 'monthly')
                        <a href="{{ route('payroll.reports.efile', ['format' => $format->key(), 'month' => $month->format('Y-m')]) }}" class="btn btn-sm btn-outline-secondary">{{ $format->label() }}</a>
                    @endif
                @endforeach
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap gap-2 align-items-center">
            <h3 class="card-title me-auto">Year-end — {{ $year }} (BIR 2316 &amp; alphalist)</h3>
            <form method="get" class="d-flex gap-2">
                <input type="number" name="year" value="{{ $year }}" min="2000" max="2100" class="form-control form-control-sm" style="width: 7rem" aria-label="Year">
                <input type="hidden" name="month" value="{{ $month->format('Y-m') }}">
                <button class="btn btn-sm btn-outline-secondary">Show</button>
            </form>
            <a href="{{ route('payroll.reports.alphalist', ['year' => $year]) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-download me-1"></i> Alphalist CSV</a>
            <a href="{{ route('payroll.reports.efile', ['format' => 'bir-alphalist', 'year' => $year]) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-download me-1"></i> Alphalist DAT</a>
        </div>
        <div class="card-body p-0 table-responsive">
            <table class="table table-sm table-striped mb-0 align-middle">
                <thead>
                    <tr><th>Employee</th><th class="text-end">Gross</th><th class="text-end">Non-taxable</th><th class="text-end">Taxable</th>
                        <th class="text-end">Tax due</th><th class="text-end">Withheld</th><th class="text-end">Adjustment</th><th class="actions"></th></tr>
                </thead>
                <tbody>
                    @forelse ($annual as $row)
                        <tr>
                            <td>{{ $row['employee']->full_name }} @if ($row['is_minimum_wage_earner'])<span class="badge text-bg-info">MWE</span>@endif</td>
                            <td class="text-end">{{ $row['gross']->format(false) }}</td>
                            <td class="text-end">{{ $row['non_taxable']->format(false) }}</td>
                            <td class="text-end">{{ $row['taxable']->format(false) }}</td>
                            <td class="text-end">{{ $row['tax_due']->format(false) }}</td>
                            <td class="text-end">{{ $row['tax_withheld']->format(false) }}</td>
                            <td class="text-end @if ($row['adjustment']->isNegative()) text-success @elseif (! $row['adjustment']->isZero()) text-danger @endif">
                                {{ $row['adjustment']->format(false) }}
                            </td>
                            <td class="actions"><a href="{{ route('payroll.reports.2316', [$year, $row['employee']]) }}" class="btn btn-sm btn-outline-secondary">2316</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-body-secondary py-4">No finalized payroll in {{ $year }}.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer small text-body-secondary">
            Adjustment = annual tax due − tax withheld: positive amounts are still to be withheld (usually on the last payroll of the year), negative amounts are refunds.
        </div>
    </div>
@stop
