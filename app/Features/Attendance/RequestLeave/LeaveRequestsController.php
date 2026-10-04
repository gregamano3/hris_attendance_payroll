<?php

namespace App\Features\Attendance\RequestLeave;

use App\Features\Attendance\Enums\LeaveStatus;
use App\Features\Attendance\Models\LeaveRequest;
use App\Features\Attendance\Models\LeaveType;
use App\Features\Attendance\Queries\LeaveBalances;
use App\Features\Employees\Models\Employee;
use App\Features\Employees\Queries\EmployeeDirectory;
use App\Shared\Security\EncryptedFiles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class LeaveRequestsController
{
    public function __construct(
        private EmployeeDirectory $directory,
        private LeaveBalances $balances,
    ) {}

    public function index(Request $request): View
    {
        $employee = $this->directory->forUser($request->user());

        return view('attendance::leaves.index', [
            'employee' => $employee,
            'requests' => $employee
                ? LeaveRequest::query()->with('leaveType')->where('employee_id', $employee->id)->latest('start_date')->paginate(15)
                : null,
            'balances' => $employee ? $this->balances->forEmployee($employee->id, today()->year) : collect(),
            'leaveTypes' => LeaveType::query()->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function store(Request $request, SubmitLeaveRequest $submit): RedirectResponse
    {
        $leave = $submit->handle($this->employeeOrFail($request), $request->all(), $request->file('attachment'), $request->user()?->id);

        return back()->with('success', "Leave request for {$leave->days} day(s) submitted.");
    }

    public function cancel(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        $employee = $this->employeeOrFail($request);

        abort_unless($leaveRequest->employee_id === $employee->id, 403);

        if ($leaveRequest->status !== LeaveStatus::Pending) {
            return back()->with('error', 'Only pending requests can be cancelled.');
        }

        $leaveRequest->update(['status' => LeaveStatus::Cancelled]);

        return back()->with('success', 'Leave request cancelled.');
    }

    private function employeeOrFail(Request $request): Employee
    {
        return $this->directory->forUser($request->user())
            ?? abort(403, 'Your account is not linked to an employee record.');
    }

    /**
     * Supporting document: the requester, HR approvers or the requester's supervisor.
     */
    public function attachment(Request $request, LeaveRequest $leaveRequest): Response
    {
        $user = $request->user();
        $own = $this->directory->forUser($user)?->id === $leaveRequest->employee_id;
        $approver = $user?->can('leaves.approve') || $this->directory->teamIdsOf($user)->contains($leaveRequest->employee_id);
        abort_unless(($own || $approver) && $leaveRequest->attachment_path, $leaveRequest->attachment_path ? 403 : 404);

        return response(EncryptedFiles::get('local', $leaveRequest->attachment_path), 200, [
            'Content-Type' => $leaveRequest->attachment_mime ?? 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="'.str_replace('"', '', (string) $leaveRequest->attachment_name).'"',
        ]);
    }
}
