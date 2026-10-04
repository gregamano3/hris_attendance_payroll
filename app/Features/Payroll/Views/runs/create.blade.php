@extends('layouts.app')

@section('title', 'New payroll run')

@section('page')
    <form method="post" action="{{ route('payroll.runs.store') }}" class="card">
        @csrf
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
@stop
