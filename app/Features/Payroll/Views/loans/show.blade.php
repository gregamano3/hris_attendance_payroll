@extends('layouts.app')

@section('title', $loan->type->label())

@section('page')
    <div class="row">
        <div class="col-lg-4">
            <div class="card card-primary card-outline">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between"><span>Employee</span><strong>{{ $loan->employee->full_name }}</strong></li>
                    <li class="list-group-item d-flex justify-content-between"><span>Reference</span><strong>{{ $loan->reference_no ?? '—' }}</strong></li>
                    <li class="list-group-item d-flex justify-content-between"><span>Principal</span><strong>{{ $loan->principal->format() }}</strong></li>
                    <li class="list-group-item d-flex justify-content-between"><span>Per run</span><strong>{{ $loan->amortization->format() }}</strong></li>
                    <li class="list-group-item d-flex justify-content-between"><span>Balance</span><strong>{{ $loan->balance->format() }}</strong></li>
                    <li class="list-group-item d-flex justify-content-between"><span>Status</span><span class="badge text-bg-{{ $loan->status->badge() }}">{{ $loan->status->label() }}</span></li>
                </ul>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Payments</h3></div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Payroll run</th><th class="text-end">Paid</th><th class="text-end">Balance after</th></tr></thead>
                        <tbody>
                            @forelse ($loan->payments as $payment)
                                <tr>
                                    <td>@if ($payment->run)<a href="{{ route('payroll.runs.show', $payment->run) }}">{{ $payment->run->name }}</a>@else Final pay @endif</td>
                                    <td class="text-end">{{ $payment->amount->format(false) }}</td>
                                    <td class="text-end">{{ $payment->balance_after->format(false) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-body-secondary py-3">No payments yet. Payments are recorded when a payroll run is finalized.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@stop
