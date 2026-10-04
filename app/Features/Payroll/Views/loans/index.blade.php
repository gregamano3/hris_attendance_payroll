@php use App\Features\Payroll\Enums\LoanStatus; @endphp
@extends('layouts.app')

@section('title', 'Loans')

@section('page')
    <div class="row">
        <div class="col-lg-8">
            <ul class="nav nav-tabs mb-3">
                @foreach (LoanStatus::cases() as $case)
                    <li class="nav-item"><a class="nav-link @if ($case === $status) active @endif" href="{{ route('payroll.loans.index', ['status' => $case->value]) }}">{{ $case->label() }}</a></li>
                @endforeach
            </ul>
            <div class="card">
                <div class="card-body p-0 table-responsive">
                    <table class="table table-sm table-striped mb-0 align-middle">
                        <thead><tr><th>Employee</th><th>Loan</th><th class="text-end">Principal</th><th class="text-end">Per run</th><th class="text-end">Balance</th><th>Start</th><th class="actions"></th></tr></thead>
                        <tbody>
                            @forelse ($loans as $loan)
                                <tr>
                                    <td>{{ $loan->employee->full_name }}</td>
                                    <td><a href="{{ route('payroll.loans.show', $loan) }}">{{ $loan->type->label() }}</a> <span class="small text-body-secondary">{{ $loan->reference_no }}</span></td>
                                    <td class="text-end">{{ $loan->principal->format(false) }}</td>
                                    <td class="text-end">{{ $loan->amortization->format(false) }}</td>
                                    <td class="text-end fw-semibold">{{ $loan->balance->format(false) }}</td>
                                    <td>{{ $loan->starts_on->format('M j, Y') }}</td>
                                    <td class="actions">
                                        @if ($loan->status === LoanStatus::Active)
                                            @can('payroll.manage')
                                                <form method="post" action="{{ route('payroll.loans.cancel', $loan) }}" data-confirm="Cancel this loan? No further deductions will be made.">
                                                    @csrf @method('patch')
                                                    <button class="btn btn-sm btn-outline-secondary">Cancel</button>
                                                </form>
                                            @endcan
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-body-secondary py-4">No loans.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($loans->hasPages())
                    <div class="card-footer">{{ $loans->links() }}</div>
                @endif
            </div>
        </div>
        @can('payroll.manage')
            <div class="col-lg-4">
                <form method="post" action="{{ route('payroll.loans.store') }}" class="card">
                    @csrf
                    <div class="card-header"><h3 class="card-title">Add loan</h3></div>
                    <div class="card-body row g-3">
                        <x-form.select name="employee_id" label="Employee" :options="$employees" col="col-12" placeholder="Select…" required />
                        <x-form.select name="type" label="Type" :options="$types" col="col-12" required />
                        <x-form.input name="reference_no" label="Reference no." col="col-12" />
                        <x-form.input name="principal" label="Amount to recover (₱)" type="number" step="0.01" min="0" col="col-6" required />
                        <x-form.input name="amortization" label="Deduction per run (₱)" type="number" step="0.01" min="0" col="col-6" required />
                        <x-form.input name="starts_on" label="First deduction from" type="date" :value="today()->toDateString()" col="col-12" required />
                        <x-form.textarea name="notes" label="Notes" />
                    </div>
                    <div class="card-footer"><button class="btn btn-primary">Add loan</button></div>
                </form>
            </div>
        @endcan
    </div>
@stop
