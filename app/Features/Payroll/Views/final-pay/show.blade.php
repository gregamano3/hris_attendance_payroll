@php use App\Features\Payroll\Enums\PayrollRunStatus; @endphp
@extends('layouts.app')

@section('title', 'Final pay — '.$finalPay->employee->full_name)

@section('page_actions')
    <div class="d-flex flex-wrap gap-2">
        @if ($finalPay->lines !== null)
            <a href="{{ route('payroll.final-pay.pdf', $finalPay) }}" class="btn btn-outline-secondary"><i class="bi bi-filetype-pdf me-1"></i> PDF</a>
        @endif
        @unless ($finalPay->isLocked())
            @can('payroll.manage')
                <form method="post" action="{{ route('payroll.final-pay.compute', $finalPay) }}">@csrf<button class="btn btn-primary"><i class="bi bi-calculator me-1"></i> Compute</button></form>
            @endcan
            @can('payroll.finalize')
                @if ($finalPay->status === PayrollRunStatus::Computed)
                    <form method="post" action="{{ route('payroll.final-pay.finalize', $finalPay) }}" data-confirm="Finalize this final pay? Outstanding loans will be settled.">
                        @csrf<button class="btn btn-success"><i class="bi bi-lock me-1"></i> Finalize</button>
                    </form>
                @endif
            @endcan
            @can('payroll.manage')
                <x-delete-button :action="route('payroll.final-pay.destroy', $finalPay)" label="Delete" class="btn-md" confirm="Delete this final pay?" />
            @endcan
        @endunless
    </div>
@stop

@push('css')
    <style>
        .payslip-lines { margin-top: 1rem; border-collapse: collapse; }
        .payslip-lines th { background: var(--bs-tertiary-bg); padding: .4rem .5rem; text-transform: uppercase; font-size: .8rem; }
        .payslip-lines td { padding: .3rem .5rem; border-bottom: 1px solid var(--bs-border-color); }
        .payslip-lines .amount { text-align: right; white-space: nowrap; }
        .payslip-lines .qty { text-align: right; color: var(--bs-secondary-color); }
        .payslip-lines .total td { font-weight: 600; }
        .payslip-lines .net td { font-weight: 700; font-size: 1.15rem; border-bottom: 0; }
    </style>
@endpush

@section('page')
    <p class="text-body-secondary">
        <span class="badge text-bg-{{ $finalPay->status->badge() }}">{{ $finalPay->status->label() }}</span>
        Separated {{ $finalPay->separation_date->format('M j, Y') }} ({{ $finalPay->employee->status->label() }})
        @if ($finalPay->finalized_at) · Finalized {{ $finalPay->finalized_at->format('M j, Y') }} by {{ $finalPay->finalizer?->name }} @endif
    </p>
    @if ($finalPay->status === PayrollRunStatus::Draft && $finalPay->computed_at)
        <div class="alert alert-warning">Adjustments changed since the last computation. Recompute before finalizing.</div>
    @endif

    <div class="row">
        <div class="col-lg-7">
            <div class="card"><div class="card-body">
                @if ($finalPay->lines === null)
                    <p class="text-body-secondary mb-0">Not computed yet.</p>
                @else
                    @include('payroll::final-pay._statement')
                @endif
            </div></div>
        </div>
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Adjustments</h3></div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        @forelse ($finalPay->adjustments as $adjustment)
                            <tr>
                                <td>{{ $adjustment->label }} <span class="small text-body-secondary">{{ $adjustment->kind }}</span></td>
                                <td class="text-end">{{ $adjustment->amount->format() }}</td>
                                <td class="actions">
                                    @if (! $finalPay->isLocked() && auth()->user()->can('payroll.manage'))
                                        <x-delete-button :action="route('payroll.final-pay.adjustments.destroy', [$finalPay, $adjustment])" label="Remove" confirm="Remove?" />
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td class="text-body-secondary py-3 text-center">No adjustments.</td></tr>
                        @endforelse
                    </table>
                </div>
                @if (! $finalPay->isLocked() && auth()->user()->can('payroll.manage'))
                    <form method="post" action="{{ route('payroll.final-pay.adjustments.store', $finalPay) }}" class="card-footer row g-2">
                        @csrf
                        <x-form.select name="kind" label="Type" :options="['earning' => 'Earning (salary differential, bonus…)', 'deduction' => 'Deduction (unreturned equipment…)']" col="col-12" />
                        <x-form.input name="label" label="Description" col="col-7" required />
                        <x-form.input name="amount" label="Amount (₱)" type="number" step="0.01" min="0" col="col-5" required />
                        <div class="col-12 form-check ms-2">
                            <input type="hidden" name="taxable" value="0">
                            <input class="form-check-input" type="checkbox" id="taxable" name="taxable" value="1" checked>
                            <label class="form-check-label" for="taxable">Taxable (earnings)</label>
                        </div>
                        <div class="col-12"><button class="btn btn-sm btn-primary">Add adjustment</button></div>
                    </form>
                @endif
            </div>
        </div>
    </div>
@stop
