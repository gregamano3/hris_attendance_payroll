<?php

namespace App\Features\Attendance\RequestLeave;

use App\Features\Attendance\Enums\LeaveStatus;
use App\Features\Attendance\Models\LeaveRequest;
use App\Features\Attendance\Models\LeaveType;
use App\Features\Attendance\Queries\LeaveBalances;
use App\Features\Employees\Models\Employee;
use App\Features\Employees\Queries\EmployeeDirectory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

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

    public function store(Request $request): RedirectResponse
    {
        $employee = $this->employeeOrFail($request);

        $data = $request->validate([
            'leave_type_id' => ['required', 'integer', Rule::exists('leave_types', 'id')],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $from = Carbon::parse($data['start_date']);
        $to = Carbon::parse($data['end_date']);
        $days = $this->balances->workingDays($employee->id, $from, $to);

        if ($days === 0) {
            throw ValidationException::withMessages(['end_date' => 'The selected dates contain no working days.']);
        }

        $overlaps = LeaveRequest::query()
            ->where('employee_id', $employee->id)
            ->whereIn('status', [LeaveStatus::Pending, LeaveStatus::Approved])
            ->whereDate('start_date', '<=', $to)
            ->whereDate('end_date', '>=', $from)
            ->exists();

        if ($overlaps) {
            throw ValidationException::withMessages(['start_date' => 'You already have a leave request for these dates.']);
        }

        $type = LeaveType::query()->findOrFail($data['leave_type_id']);
        $balance = $this->balances->forEmployee($employee->id, $from->year)->firstWhere('type.id', $type->id);

        if ($balance !== null && $balance['remaining'] !== null && $days > $balance['remaining']) {
            throw ValidationException::withMessages([
                'end_date' => "Only {$balance['remaining']} day(s) of {$type->name} remain this year.",
            ]);
        }

        LeaveRequest::query()->create([
            ...$data,
            'employee_id' => $employee->id,
            'days' => $days,
            'status' => LeaveStatus::Pending,
        ]);

        return back()->with('success', "Leave request for {$days} day(s) submitted.");
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
}
