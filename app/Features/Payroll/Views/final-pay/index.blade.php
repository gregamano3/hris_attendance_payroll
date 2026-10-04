@extends('layouts.app')

@section('title', 'Final pay')

@section('page')
    <div class="callout callout-info small">
        Pay the last salary through the regular payroll run that covers the separation date. The final pay settles the pro-rated
        13th month, unused convertible leave, outstanding loans and the year-end tax annualization. DOLE requires release within 30 days of separation.
    </div>

    @if ($pending->isNotEmpty())
        <div class="card card-warning card-outline">
            <div class="card-header"><h3 class="card-title">Separated employees without final pay</h3></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0 align-middle">
                    <tbody>
                        @foreach ($pending as $employee)
                            <tr>
                                <td>{{ $employee->full_name }} <span class="small text-body-secondary">{{ $employee->employee_no }}</span></td>
                                <td>{{ $employee->status->label() }} {{ $employee->separated_at->format('M j, Y') }}</td>
                                <td class="actions">
                                    @can('payroll.manage')
                                        <form method="post" action="{{ route('payroll.final-pay.store') }}">
                                            @csrf
                                            <input type="hidden" name="employee_id" value="{{ $employee->id }}">
                                            <button class="btn btn-sm btn-primary">Prepare final pay</button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-body p-0 table-responsive">
            <table class="table table-striped mb-0 align-middle">
                <thead><tr><th>Employee</th><th>Separation</th><th class="text-end">Earnings</th><th class="text-end">Deductions</th><th class="text-end">Net</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse ($finalPays as $finalPay)
                        <tr>
                            <td><a href="{{ route('payroll.final-pay.show', $finalPay) }}">{{ $finalPay->employee->full_name }}</a></td>
                            <td>{{ $finalPay->separation_date->format('M j, Y') }}</td>
                            <td class="text-end">{{ $finalPay->total_earnings->format(false) }}</td>
                            <td class="text-end">{{ $finalPay->total_deductions->format(false) }}</td>
                            <td class="text-end fw-semibold">{{ $finalPay->net_pay->format(false) }}</td>
                            <td><span class="badge text-bg-{{ $finalPay->status->badge() }}">{{ $finalPay->status->label() }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-body-secondary py-4">No final pay computations yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@stop
