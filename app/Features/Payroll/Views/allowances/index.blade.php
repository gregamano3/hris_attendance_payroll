@extends('layouts.app')

@section('title', 'Recurring allowances')

@section('page')
    <div class="row">
        <div class="col-lg-8">
            <form method="get" class="card card-body flex-row gap-2 mb-3">
                <select name="employee" class="form-select" aria-label="Employee">
                    <option value="">All employees</option>
                    @foreach ($employees as $id => $label)
                        <option value="{{ $id }}" @selected($employeeId === $id)>{{ $label }}</option>
                    @endforeach
                </select>
                <button class="btn btn-outline-secondary">Filter</button>
            </form>
            <div class="card">
                <div class="card-body p-0 table-responsive">
                    <table class="table table-sm table-striped mb-0 align-middle">
                        <thead><tr><th>Employee</th><th>Allowance</th><th class="text-end">Per run</th><th>Tax</th><th>From</th><th>Until</th><th class="actions"></th></tr></thead>
                        <tbody>
                            @forelse ($earnings as $earning)
                                <tr>
                                    <td>{{ $earning->employee->full_name }}</td>
                                    <td>{{ $earning->label }}</td>
                                    <td class="text-end">{{ $earning->amount->format() }}</td>
                                    <td class="small">{{ $earning->tax_treatment->isTaxable() ? 'Taxable' : 'Non-taxable' }}</td>
                                    <td>{{ $earning->starts_on->format('M j, Y') }}</td>
                                    <td>
                                        @if ($earning->ends_on)
                                            {{ $earning->ends_on->format('M j, Y') }}
                                        @elsecan('payroll.manage')
                                            <form method="post" action="{{ route('payroll.allowances.update', $earning) }}" class="d-flex gap-1">
                                                @csrf @method('patch')
                                                <input type="date" name="ends_on" class="form-control form-control-sm" aria-label="End date" required>
                                                <button class="btn btn-sm btn-outline-secondary">End</button>
                                            </form>
                                        @else
                                            Open-ended
                                        @endif
                                    </td>
                                    <td class="actions">
                                        @can('payroll.manage')
                                            <x-delete-button :action="route('payroll.allowances.destroy', $earning)" label="Remove" confirm="Remove this allowance?" />
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-body-secondary py-4">No recurring allowances.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($earnings->hasPages())
                    <div class="card-footer">{{ $earnings->links() }}</div>
                @endif
            </div>
        </div>
        @can('payroll.manage')
            <div class="col-lg-4">
                <form method="post" action="{{ route('payroll.allowances.store') }}" class="card">
                    @csrf
                    <div class="card-header"><h3 class="card-title">Add recurring allowance</h3></div>
                    <div class="card-body row g-3">
                        <x-form.select name="employee_id" label="Employee" :options="$employees" col="col-12" placeholder="Select…" required />
                        <x-form.input name="label" label="Description" col="col-12" placeholder="e.g. Rice subsidy" required />
                        <x-form.input name="amount" label="Amount per payroll run (₱)" type="number" step="0.01" min="0" col="col-12" required />
                        <x-form.select name="tax_treatment" label="Tax treatment" :options="$treatments" col="col-12" required />
                        <x-form.input name="starts_on" label="From" type="date" col="col-6" :value="today()->toDateString()" required />
                        <x-form.input name="ends_on" label="Until (optional)" type="date" col="col-6" />
                    </div>
                    <div class="card-footer"><button class="btn btn-primary">Add</button></div>
                </form>
            </div>
        @endcan
    </div>
@stop
