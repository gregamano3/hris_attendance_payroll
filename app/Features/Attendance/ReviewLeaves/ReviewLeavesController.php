<?php

namespace App\Features\Attendance\ReviewLeaves;

use App\Features\Attendance\Enums\LeaveStatus;
use App\Features\Attendance\Models\LeaveRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReviewLeavesController
{
    public function index(Request $request): View
    {
        $status = LeaveStatus::tryFrom($request->string('status')->toString()) ?? LeaveStatus::Pending;

        return view('attendance::leaves.review', [
            'status' => $status,
            'requests' => LeaveRequest::query()
                ->with(['employee', 'leaveType', 'reviewer'])
                ->where('status', $status)
                ->orderBy('start_date')
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function update(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in([LeaveStatus::Approved->value, LeaveStatus::Rejected->value])],
            'review_remarks' => ['nullable', 'string', 'max:255'],
        ]);

        if ($leaveRequest->status !== LeaveStatus::Pending) {
            return back()->with('error', 'This request was already reviewed.');
        }

        if ($leaveRequest->employee->user_id !== null && $leaveRequest->employee->user_id === $request->user()?->id) {
            return back()->with('error', 'You cannot review your own leave request.');
        }

        $leaveRequest->update([
            'status' => LeaveStatus::from($data['decision']),
            'review_remarks' => $data['review_remarks'] ?? null,
            'reviewed_by' => $request->user()?->id,
            'reviewed_at' => now(),
        ]);

        return back()->with('success', "Leave request {$leaveRequest->status->label()}.");
    }
}
