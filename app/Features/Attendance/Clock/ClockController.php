<?php

namespace App\Features\Attendance\Clock;

use App\Features\Attendance\Enums\TimeLogType;
use App\Features\Attendance\Models\TimeLog;
use App\Features\Employees\Queries\EmployeeDirectory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ClockController
{
    public function __construct(private EmployeeDirectory $directory, private RecordPunch $punch) {}

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
            'nextType' => $employee ? $this->punch->nextType($employee) : TimeLogType::In,
            'breakAction' => $employee ? $this->punch->breakAction($employee) : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $employee = $this->directory->forUser($request->user());
        abort_if($employee === null, 403, 'Your account is not linked to an employee record.');

        $request->validate([
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        try {
            $log = $this->punch->handle(
                $employee,
                $request->input('action') === 'break' ? 'break' : 'main',
                $request->ip(),
                $request->filled('latitude') ? (float) $request->input('latitude') : null,
                $request->filled('longitude') ? (float) $request->input('longitude') : null,
                $request->user()?->id,
            );
        } catch (ValidationException $e) {
            $message = (string) collect($e->errors())->flatten()->first();

            return back()->with(str_starts_with($message, 'You just punched') ? 'warning' : 'error', $message);
        }

        return back()->with('success', sprintf('%s recorded at %s.', $log->type->label(), now()->format('g:i A')));
    }
}
