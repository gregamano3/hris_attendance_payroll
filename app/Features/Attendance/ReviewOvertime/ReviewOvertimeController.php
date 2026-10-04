<?php

namespace App\Features\Attendance\ReviewOvertime;

use App\Features\Attendance\Enums\LeaveStatus;
use App\Features\Attendance\Models\AttendanceDay;
use App\Features\Attendance\Models\OvertimeRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReviewOvertimeController
{
    public function index(Request $request): View
    {
        $status = LeaveStatus::tryFrom($request->string('status')->toString()) ?? LeaveStatus::Pending;

        $requests = OvertimeRequest::query()
            ->with(['employee', 'reviewer'])
            ->where('status', $status)
            ->orderBy('date')
            ->paginate(20)
            ->withQueryString();

        // Show the punches of each date so approvers can compare with actual time out.
        $days = AttendanceDay::query()
            ->whereIn('employee_id', $requests->pluck('employee_id'))
            ->whereIn('date', $requests->map(fn (OvertimeRequest $r) => $r->date->toDateString()))
            ->get()
            ->keyBy(fn (AttendanceDay $d) => $d->employee_id.'|'.$d->date->toDateString());

        return view('attendance::overtime.review', compact('status', 'requests', 'days'));
    }

    public function update(Request $request, OvertimeRequest $overtimeRequest): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in([LeaveStatus::Approved->value, LeaveStatus::Rejected->value])],
            'review_remarks' => ['nullable', 'string', 'max:255'],
        ]);

        if ($overtimeRequest->status !== LeaveStatus::Pending) {
            return back()->with('error', 'This request was already reviewed.');
        }

        $employeeUserId = $overtimeRequest->employee->user_id;

        if ($employeeUserId !== null && $employeeUserId === $request->user()?->id) {
            return back()->with('error', 'You cannot review your own overtime request.');
        }

        $overtimeRequest->update([
            'status' => LeaveStatus::from($data['decision']),
            'review_remarks' => $data['review_remarks'] ?? null,
            'reviewed_by' => $request->user()?->id,
            'reviewed_at' => now(),
        ]);

        return back()->with('success', "Overtime request {$overtimeRequest->status->label()}.");
    }
}
