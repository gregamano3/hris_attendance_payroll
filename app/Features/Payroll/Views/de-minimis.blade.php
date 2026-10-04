@extends('layouts.app')

@section('title', 'De minimis benefits')

@section('page')
    <div class="callout callout-warning small">
        Amounts up to these ceilings are tax-exempt; recurring allowances linked to a benefit are split automatically into an exempt part and a
        taxable excess (monthly ceilings are pro-rated per payroll run, annual ceilings are tracked over the year's finalized payroll).
        Verify the ceilings against the latest BIR revenue regulations.
    </div>
    <div class="card">
        <div class="card-body p-0 table-responsive">
            <table class="table table-sm mb-0 align-middle">
                <thead><tr><th>Code</th><th>Benefit</th><th>Ceiling (₱)</th><th>Per</th><th></th></tr></thead>
                <tbody>
                    @foreach ($benefits as $benefit)
                        <tr>
                            <form method="post" action="{{ route('payroll.de-minimis.update', $benefit) }}">
                                @csrf @method('put')
                                <td><span class="badge text-bg-light border">{{ $benefit->code }}</span></td>
                                <td><input name="name" value="{{ $benefit->name }}" class="form-control form-control-sm" aria-label="Name"></td>
                                <td><input type="number" step="0.01" min="0" name="limit_amount" value="{{ $benefit->limit_amount->toDecimal() }}" class="form-control form-control-sm" style="width:9rem" aria-label="Ceiling"></td>
                                <td>
                                    <select name="period" class="form-select form-select-sm" aria-label="Period">
                                        <option value="monthly" @selected($benefit->period === 'monthly')>Month</option>
                                        <option value="annual" @selected($benefit->period === 'annual')>Year</option>
                                    </select>
                                </td>
                                <td><button class="btn btn-sm btn-outline-primary">Save</button></td>
                            </form>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <form method="post" action="{{ route('payroll.de-minimis.store') }}" class="card">
        @csrf
        <div class="card-header"><h3 class="card-title">Add benefit</h3></div>
        <div class="card-body row g-3">
            <x-form.input name="code" label="Code" col="col-md-2" required />
            <x-form.input name="name" label="Name" col="col-md-5" required />
            <x-form.input name="limit_amount" label="Ceiling (₱)" type="number" step="0.01" min="0" col="col-md-3" required />
            <x-form.select name="period" label="Per" :options="['monthly' => 'Month', 'annual' => 'Year']" col="col-md-2" />
        </div>
        <div class="card-footer"><button class="btn btn-primary">Add</button></div>
    </form>
@stop
