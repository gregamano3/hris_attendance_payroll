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
            'requiresLocation' => app(ClockRestrictions::class)->requiresLocation($employee?->branch),
            'logs' => $logs,
            'nextType' => $employee ? $this->nextType($employee) : TimeLogType::In,
            'breakAction' => $employee ? $this->breakAction($employee) : null,
        ]);
    }

    public function store(Request $request, ClockRestrictions $restrictions): RedirectResponse
    {
        $employee = $this->directory->forUser($request->user());
        abort_if($employee === null, 403, 'Your account is not linked to an employee record.');

        $request->validate([
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $check = $restrictions->check(
            $employee->branch,
            $request->ip(),
            $request->filled('latitude') ? (float) $request->input('latitude') : null,
            $request->filled('longitude') ? (float) $request->input('longitude') : null,
        );

        if (! $check['allowed']) {
            return back()->with('error', $check['reason']);
        }

        $type = $request->input('action') === 'break' ? $this->breakAction($employee) : $this->nextType($employee);

        if ($type === null) {
            return back()->with('error', 'Clock in before taking a break.');
        }

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
            'distance_m' => $check['distance_m'],
            'created_by' => $request->user()?->id,
        ]);

        return back()->with('success', sprintf('%s recorded at %s.', $type->label(), now()->format('g:i A')));
    }

    /**
     * Main button: in → out, ending a break first when on break.
     */
    private function nextType(Employee $employee): TimeLogType
    {
        return match ($this->lastType($employee)) {
            TimeLogType::In, TimeLogType::BreakIn => TimeLogType::Out,
            TimeLogType::BreakOut => TimeLogType::BreakIn,
            default => TimeLogType::In,
        };
    }

    /**
     * Break button: start a break while clocked in, end it while on break.
     */
    private function breakAction(Employee $employee): ?TimeLogType
    {
        return match ($this->lastType($employee)) {
            TimeLogType::In, TimeLogType::BreakIn => TimeLogType::BreakOut,
            TimeLogType::BreakOut => TimeLogType::BreakIn,
            default => null,
        };
    }

    private function lastType(Employee $employee): ?TimeLogType
    {
        return TimeLog::query()
            ->where('employee_id', $employee->id)
            ->where('logged_at', '>=', now()->subHours(16))
            ->latest('logged_at')
            ->first()?->type;
    }
}
