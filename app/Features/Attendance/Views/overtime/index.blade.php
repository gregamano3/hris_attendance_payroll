@php use App\Features\Attendance\Enums\LeaveStatus; use App\Shared\Format; @endphp
@extends('layouts.app')

@section('title', 'My overtime')

@section('page')
    @if (! $employee)
        <div class="callout callout-info">Your account is not linked to an employee record. Please contact Human Resources.</div>
    @else
        @if (config('hris.attendance.overtime_requires_approval'))
            <div class="callout callout-info small">Only approved overtime is paid, up to the approved hours. File your request before or within 31 days after the overtime.</div>
        @endif
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-striped mb-0 align-middle">
                            <thead><tr><th>Date</th><th class="text-end">Hours</th><th>Reason</th><th>Status</th><th>Remarks</th><th class="actions"></th></tr></thead>
                            <tbody>
                                @forelse ($requests as $overtime)
                                    <tr>
                                        <td class="text-nowrap">{{ $overtime->date->format('D, M j, Y') }}</td>
                                        <td class="text-end">{{ Format::minutes($overtime->minutes) }}</td>
                                        <td class="small">{{ $overtime->reason }}</td>
                                        <td><span class="badge text-bg-{{ $overtime->status->badge() }}">{{ $overtime->status->label() }}</span></td>
                                        <td class="small">{{ $overtime->review_remarks }}</td>
                                        <td class="actions">
                                            @if ($overtime->status === LeaveStatus::Pending)
                                                <form method="post" action="{{ route('overtime.cancel', $overtime) }}" data-confirm="Cancel this request?">
                                                    @csrf @method('patch')
                                                    <button class="btn btn-sm btn-outline-secondary">Cancel</button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-body-secondary py-4">No overtime requests yet.</td></tr>
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
                <form method="post" action="{{ route('overtime.store') }}" class="card">
                    @csrf
                    <div class="card-header"><h3 class="card-title">Request overtime</h3></div>
                    <div class="card-body row g-3">
                        <x-form.input name="date" label="Date" type="date" col="col-6" required />
                        <x-form.input name="hours" label="Hours" type="number" step="0.5" min="0.5" max="16" col="col-6" required />
                        <x-form.textarea name="reason" label="Reason" rows="3" required />
                    </div>
                    <div class="card-footer"><button class="btn btn-primary">Submit request</button></div>
                </form>
            </div>
        </div>
    @endif
@stop
