@php use App\Features\Attendance\Enums\LeaveStatus; use App\Shared\Format; @endphp
@extends('layouts.app')

@section('title', 'Overtime approvals')

@section('page')
    <ul class="nav nav-tabs mb-3">
        @foreach (LeaveStatus::cases() as $case)
            <li class="nav-item">
                <a class="nav-link @if ($case === $status) active @endif" href="{{ route('overtime.review', ['status' => $case->value]) }}">{{ $case->label() }}</a>
            </li>
        @endforeach
    </ul>

    <div class="card">
        <div class="card-body p-0 table-responsive">
            <table class="table table-striped mb-0 align-middle">
                <thead>
                    <tr><th>Employee</th><th>Date</th><th class="text-end">Requested</th><th>Actual time out</th><th>Reason</th>
                        @if ($status === LeaveStatus::Pending)<th>Decision</th>@else<th>Reviewed by</th>@endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $overtime)
                        @php $day = $days->get($overtime->employee_id.'|'.$overtime->date->toDateString()); @endphp
                        <tr>
                            <td>{{ $overtime->employee->full_name }}</td>
                            <td class="text-nowrap">{{ $overtime->date->format('D, M j, Y') }}</td>
                            <td class="text-end">{{ Format::minutes($overtime->minutes) }}</td>
                            <td>{{ $day?->time_out?->format('g:i A') ?? '—' }}</td>
                            <td class="small">{{ $overtime->reason }}</td>
                            <td>
                                @if ($status === LeaveStatus::Pending)
                                    <form method="post" action="{{ route('overtime.review.update', $overtime) }}" class="d-flex gap-1">
                                        @csrf @method('patch')
                                        <input type="text" name="review_remarks" class="form-control form-control-sm" placeholder="Remarks" aria-label="Remarks">
                                        <button name="decision" value="approved" class="btn btn-sm btn-success">Approve</button>
                                        <button name="decision" value="rejected" class="btn btn-sm btn-outline-danger">Reject</button>
                                    </form>
                                @else
                                    {{ $overtime->reviewer?->name ?? '—' }}
                                    <div class="small text-body-secondary">{{ $overtime->review_remarks }}</div>
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
