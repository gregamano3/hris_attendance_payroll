<?php

namespace App\Features\Attendance\RequestOvertime;

use App\Features\Attendance\Enums\LeaveStatus;
use App\Features\Attendance\Models\OvertimeRequest;
use App\Features\Employees\Models\Employee;
use App\Features\Employees\Queries\EmployeeDirectory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OvertimeRequestsController
{
    public function __construct(private EmployeeDirectory $directory) {}

    public function index(Request $request): View
    {
        $employee = $this->directory->forUser($request->user());

        return view('attendance::overtime.index', [
            'employee' => $employee,
            'requests' => $employee
                ? OvertimeRequest::query()->where('employee_id', $employee->id)->latest('date')->paginate(15)
                : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $employee = $this->employeeOrFail($request);

        $data = $request->validate([
            'date' => ['required', 'date', 'after_or_equal:'.today()->subDays(31)->toDateString(), 'before_or_equal:'.today()->addDays(31)->toDateString()],
            'hours' => ['required', 'numeric', 'min:0.5', 'max:16'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $pending = OvertimeRequest::query()
            ->where('employee_id', $employee->id)
            ->whereDate('date', $data['date'])
            ->where('status', LeaveStatus::Pending)
            ->exists();

        if ($pending) {
            return back()->withInput()->withErrors(['date' => 'You already have a pending overtime request for this date.']);
        }

        OvertimeRequest::query()->create([
            'employee_id' => $employee->id,
            'date' => $data['date'],
            'minutes' => (int) round((float) $data['hours'] * 60),
            'reason' => $data['reason'],
            'status' => LeaveStatus::Pending,
        ]);

        return back()->with('success', 'Overtime request submitted.');
    }

    public function cancel(Request $request, OvertimeRequest $overtimeRequest): RedirectResponse
    {
        abort_unless($overtimeRequest->employee_id === $this->employeeOrFail($request)->id, 403);

        if ($overtimeRequest->status !== LeaveStatus::Pending) {
            return back()->with('error', 'Only pending requests can be cancelled.');
        }

        $overtimeRequest->update(['status' => LeaveStatus::Cancelled]);

        return back()->with('success', 'Overtime request cancelled.');
    }

    private function employeeOrFail(Request $request): Employee
    {
        return $this->directory->forUser($request->user())
            ?? abort(403, 'Your account is not linked to an employee record.');
    }
}
