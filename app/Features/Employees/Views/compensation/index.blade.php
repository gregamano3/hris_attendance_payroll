@extends('layouts.app')

@section('title', 'Compensation — '.$employee->full_name)

@section('page_actions')
    <a href="{{ route('employees.show', $employee) }}" class="btn btn-outline-secondary">Back to profile</a>
@stop

@section('page')
    <div class="row">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Salary history</h3></div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0 align-middle">
                        <thead><tr><th>Effective</th><th>Rate</th><th>Reason</th><th>By</th><th class="actions"></th></tr></thead>
                        <tbody>
                            @foreach ($employee->compensationChanges as $change)
                                <tr @class(['table-warning' => $change->effective_from->isFuture()])>
                                    <td class="text-nowrap">{{ $change->effective_from->format('M j, Y') }} @if ($change->effective_from->isFuture())<span class="badge text-bg-warning">Scheduled</span>@endif</td>
                                    <td class="text-nowrap">{{ $change->basic_rate->format() }} / {{ $change->rate_type->unit() }}</td>
                                    <td class="small">{{ $change->reason }}</td>
                                    <td class="small">{{ $change->creator?->name ?? '—' }}</td>
                                    <td class="actions"><x-delete-button :action="route('employees.compensation.destroy', [$employee, $change])" label="Remove" confirm="Remove this rate change?" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @if ($lastFinalizedPeriodEnd)
                <div class="callout callout-info small">
                    Payroll is finalized for this employee up to <strong>{{ $lastFinalizedPeriodEnd->format('M j, Y') }}</strong>.
                    A change effective on or before that date is retroactive: add the difference as <em>back pay</em> from the next draft payroll run.
                </div>
            @endif
        </div>
        <div class="col-lg-5">
            <form method="post" action="{{ route('employees.compensation.store', $employee) }}" class="card">
                @csrf
                <div class="card-header"><h3 class="card-title">Record a rate change</h3></div>
                <div class="card-body row g-3">
                    <x-form.input name="effective_from" label="Effective from" type="date" :value="today()->toDateString()" col="col-md-6" required />
                    <x-form.select name="rate_type" label="Rate type" :options="$rateTypes" :value="$employee->rate_type" col="col-md-6" required />
                    <x-form.input name="basic_rate" label="Basic rate (₱)" type="number" step="0.01" min="0" :value="$employee->basic_rate->toDecimal()" col="col-12" required />
                    <x-form.input name="reason" label="Reason" col="col-12" placeholder="e.g. Annual increase, promotion, wage order" required />
                </div>
                <div class="card-footer"><button class="btn btn-primary">Save</button></div>
            </form>
        </div>
    </div>
@stop
