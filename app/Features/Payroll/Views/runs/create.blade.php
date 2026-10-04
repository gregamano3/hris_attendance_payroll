@extends('layouts.app')

@section('title', 'New payroll run')

@section('page')
    <ul class="nav nav-tabs mb-3" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link @unless (old('type') === 'thirteenth_month') active @endunless" data-bs-toggle="tab" data-bs-target="#regular" type="button" role="tab">Regular payroll</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link @if (old('type') === 'thirteenth_month') active @endif" data-bs-toggle="tab" data-bs-target="#thirteenth" type="button" role="tab">13th month pay</button>
        </li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade @unless (old('type') === 'thirteenth_month') show active @endunless" id="regular" role="tabpanel">
            <form method="post" action="{{ route('payroll.runs.store') }}" class="card">
                @csrf
                <input type="hidden" name="type" value="regular">
                <div class="card-body row g-3">
                    <x-form.input name="period_start" label="Period start" type="date" :value="$period->from->toDateString()" col="col-md-4" required />
                    <x-form.input name="period_end" label="Period end" type="date" :value="$period->to->toDateString()" col="col-md-4" required />
                    <x-form.input name="pay_date" label="Pay date" type="date" :value="$payDate->toDateString()" col="col-md-4" required />
                    <x-form.textarea name="notes" label="Notes" />
                    <div class="col-12">
                        <div class="callout callout-info mb-0 small">
                            Contributions are deducted at {{ config('hris.payroll.contribution_fraction') * 100 }}% of the monthly amount per run and
                            withholding tax uses the {{ str_replace('_', '-', config('hris.payroll.tax_frequency')) }} table, which suits semi-monthly runs.
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex gap-2">
                    <button class="btn btn-primary">Create run</button>
                    <a href="{{ route('payroll.runs.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>

        <div class="tab-pane fade @if (old('type') === 'thirteenth_month') show active @endif" id="thirteenth" role="tabpanel">
            <form method="post" action="{{ route('payroll.runs.store') }}" class="card">
                @csrf
                <input type="hidden" name="type" value="thirteenth_month">
                <div class="card-body row g-3">
                    <x-form.input name="year" label="Year" type="number" min="2000" max="2100" :value="today()->year" col="col-md-4" required />
                    <x-form.input name="pay_date" label="Pay date" type="date" id="thirteenth_pay_date" :value="today()->year.'-12-24'" col="col-md-4" required />
                    <div class="col-12">
                        <div class="callout callout-info mb-0 small">
                            13th month pay is 1/12 of the basic salary earned in the year (basic pay less absences and tardiness, plus paid leaves)
                            taken from <strong>finalized</strong> regular payroll runs. Finalize the year's last regular run first.
                            Up to ₱{{ number_format(config('hris.payroll.thirteenth_month_exempt_ceiling')) }} is tax-exempt; no contributions are deducted.
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex gap-2">
                    <button class="btn btn-primary">Create 13th month run</button>
                    <a href="{{ route('payroll.runs.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@stop
