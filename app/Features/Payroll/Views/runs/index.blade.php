@extends('layouts.app')

@section('title', 'Payroll runs')

@section('page_actions')
    @can('payroll.manage')
        <a href="{{ route('payroll.runs.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> New payroll run</a>
    @endcan
@stop

@section('page')
    <div class="card">
        <div class="card-body p-0 table-responsive">
            <table class="table table-hover table-striped mb-0 align-middle">
                <thead>
                    <tr><th>Run</th><th>Period</th><th>Pay date</th><th class="text-end">Employees</th><th class="text-end">Gross</th><th class="text-end">Net</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @forelse ($runs as $run)
                        <tr>
                            <td><a href="{{ route('payroll.runs.show', $run) }}">{{ $run->name }}</a>
                                @if ($run->isThirteenthMonth())<span class="badge text-bg-light border">13th month</span>@elseif ($run->frequency->value !== 'semi_monthly')<span class="badge text-bg-light border">{{ $run->frequency->label() }}</span>@endif</td>
                            <td>{{ $run->period()->label() }}</td>
                            <td>{{ $run->pay_date->format('M j, Y') }}</td>
                            <td class="text-end">{{ $run->employee_count }}</td>
                            <td class="text-end">{{ $run->total_gross->format() }}</td>
                            <td class="text-end">{{ $run->total_net->format() }}</td>
                            <td><span class="badge text-bg-{{ $run->status->badge() }}">{{ $run->status->label() }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-body-secondary py-4">No payroll runs yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($runs->hasPages())
            <div class="card-footer">{{ $runs->links() }}</div>
        @endif
    </div>
@stop
