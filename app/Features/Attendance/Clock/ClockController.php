<?php

namespace App\Features\Attendance\Clock;

use App\Features\Attendance\Enums\TimeLogSource;
use App\Features\Attendance\Enums\TimeLogType;
use App\Features\Attendance\Models\TimeLog;
use App\Features\Employees\Models\Employee;
use App\Features\Employees\Queries\EmployeeDirectory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClockController
{
    public function __construct(private EmployeeDirectory $directory) {}

    public function show(Request $request): View
    {
        $employee = $this->directory->forUser($request->user());

        $logs = $employee
            ? TimeLog::query()->where('employee_id', $employee->id)->where('logged_at', '>=', today()->subDay())->latest('logged_at')->get()
            : collect();

        return view('attendance::clock', [
            'employee' => $employee,
            'logs' => $logs,
            'nextType' => $employee ? $this->nextType($employee) : TimeLogType::In,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $employee = $this->directory->forUser($request->user());
        abort_if($employee === null, 403, 'Your account is not linked to an employee record.');

        $type = $this->nextType($employee);

        $recent = TimeLog::query()
            ->where('employee_id', $employee->id)
            ->where('type', $type)
            ->where('logged_at', '>=', now()->subMinute())
            ->exists();

        if ($recent) {
            return back()->with('warning', 'You just punched. Please wait a minute before trying again.');
        }

        TimeLog::query()->create([
            'employee_id' => $employee->id,
            'logged_at' => now(),
            'type' => $type,
            'source' => TimeLogSource::Web,
            'ip_address' => $request->ip(),
            'created_by' => $request->user()?->id,
        ]);

        return back()->with('success', sprintf('%s recorded at %s.', $type->label(), now()->format('g:i A')));
    }

    /**
     * Alternate between in and out based on the latest punch of the last 16 hours.
     */
    private function nextType(Employee $employee): TimeLogType
    {
        $last = TimeLog::query()
            ->where('employee_id', $employee->id)
            ->where('logged_at', '>=', now()->subHours(16))
            ->latest('logged_at')
            ->first();

        return $last?->type === TimeLogType::In ? TimeLogType::Out : TimeLogType::In;
    }
}
