@php use App\Features\Attendance\Enums\LeaveStatus; @endphp
@extends('layouts.app')

@section('title', 'Leave approvals')

@section('page')
    <ul class="nav nav-tabs mb-3">
        @foreach (LeaveStatus::cases() as $case)
            <li class="nav-item">
                <a class="nav-link @if ($case === $status) active @endif" href="{{ route('leaves.review', ['status' => $case->value]) }}">{{ $case->label() }}</a>
            </li>
        @endforeach
    </ul>

    <div class="card">
        <div class="card-body p-0 table-responsive">
            <table class="table table-striped mb-0 align-middle">
                <thead>
                    <tr><th>Employee</th><th>Type</th><th>Dates</th><th class="text-end">Days</th><th>Reason</th>
                        @if ($status === LeaveStatus::Pending)<th>Decision</th>@else<th>Reviewed by</th>@endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $leave)
                        <tr>
                            <td>{{ $leave->employee->full_name }}</td>
                            <td>{{ $leave->leaveType->name }}
                                @if ($leave->attachment_path)<a href="{{ route('leaves.attachment', $leave) }}" title="Supporting document"><i class="bi bi-paperclip"></i></a>@endif</td>
                            <td class="text-nowrap">{{ $leave->start_date->format('M j') }} – {{ $leave->end_date->format('M j, Y') }}
                                @if ($leave->day_part !== 'full')<span class="badge text-bg-light border">{{ $leave->dayPartLabel() }}</span>@endif</td>
                            <td class="text-end">{{ (float) $leave->days }}</td>
                            <td class="small">{{ $leave->reason }}</td>
                            <td>
                                @if ($status === LeaveStatus::Pending)
                                    <form method="post" action="{{ route('leaves.review.update', $leave) }}" class="d-flex gap-1">
                                        @csrf @method('patch')
                                        <input type="text" name="review_remarks" class="form-control form-control-sm" placeholder="Remarks" aria-label="Remarks">
                                        <button name="decision" value="approved" class="btn btn-sm btn-success">Approve</button>
                                        <button name="decision" value="rejected" class="btn btn-sm btn-outline-danger">Reject</button>
                                    </form>
                                @else
                                    {{ $leave->reviewer?->name ?? '—' }}
                                    <div class="small text-body-secondary">{{ $leave->review_remarks }}</div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-body-secondary py-4">Nothing here.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($requests->hasPages())
            <div class="card-footer">{{ $requests->links() }}</div>
        @endif
    </div>
@stop
