@extends('layouts.app')

@section('title', 'My leaves')

@section('page')
    @if (! $employee)
        <div class="callout callout-info">Your account is not linked to an employee record. Please contact Human Resources.</div>
    @else
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Balances {{ today()->year }}</h3></div>
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Leave type</th><th class="text-end">Allowance</th><th class="text-end">Used / pending</th><th class="text-end">Remaining</th></tr></thead>
                            <tbody>
                                @foreach ($balances as $balance)
                                    <tr>
                                        <td>{{ $balance['type']->name }} @unless ($balance['type']->is_paid)<span class="badge text-bg-secondary">Unpaid</span>@endunless</td>
                                        <td class="text-end">{{ $balance['allowance'] ?: '—' }}</td>
                                        <td class="text-end">{{ rtrim(rtrim(number_format($balance['used'], 1), '0'), '.') }}</td>
                                        <td class="text-end">{{ $balance['remaining'] === null ? '—' : rtrim(rtrim(number_format($balance['remaining'], 1), '0'), '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h3 class="card-title">My requests</h3></div>
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-striped mb-0 align-middle">
                            <thead><tr><th>Type</th><th>Dates</th><th class="text-end">Days</th><th>Status</th><th>Remarks</th><th class="actions"></th></tr></thead>
                            <tbody>
                                @forelse ($requests as $leave)
                                    <tr>
                                        <td>{{ $leave->leaveType->name }}</td>
                                        <td class="text-nowrap">{{ $leave->start_date->format('M j') }} – {{ $leave->end_date->format('M j, Y') }}</td>
                                        <td class="text-end">{{ (float) $leave->days }}</td>
                                        <td><span class="badge text-bg-{{ $leave->status->badge() }}">{{ $leave->status->label() }}</span></td>
                                        <td class="small">{{ $leave->review_remarks }}</td>
                                        <td class="actions">
                                            @if ($leave->status === \App\Features\Attendance\Enums\LeaveStatus::Pending)
                                                <form method="post" action="{{ route('leaves.cancel', $leave) }}" data-confirm="Cancel this request?">
                                                    @csrf @method('patch')
                                                    <button class="btn btn-sm btn-outline-secondary">Cancel</button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-body-secondary py-4">No leave requests yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($requests->hasPages())
                        <div class="card-footer">{{ $requests->links() }}</div>
                    @endif
                </div>
            </div>

            <div class="col-lg-4">
                <form method="post" action="{{ route('leaves.store') }}" class="card">
                    @csrf
                    <div class="card-header"><h3 class="card-title">Request leave</h3></div>
                    <div class="card-body row g-3">
                        <x-form.select name="leave_type_id" label="Leave type" :options="$leaveTypes" col="col-12" placeholder="Select…" required />
                        <x-form.input name="start_date" label="From" type="date" col="col-6" required />
                        <x-form.input name="end_date" label="To" type="date" col="col-6" required />
                        <x-form.textarea name="reason" label="Reason" rows="3" />
                    </div>
                    <div class="card-footer"><button class="btn btn-primary">Submit request</button></div>
                </form>
            </div>
        </div>
    @endif
@stop
