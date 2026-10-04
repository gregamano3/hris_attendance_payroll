<?php

namespace App\Features\Attendance\Clock;

use App\Features\Attendance\Enums\TimeLogSource;
use App\Features\Attendance\Enums\TimeLogType;
use App\Features\Attendance\Models\TimeLog;
use App\Features\Employees\Models\Employee;
use Illuminate\Validation\ValidationException;

/**
 * Records an employee's own punch (web clock or API), applying the branch
 * clock restrictions and the double-punch guard.
 */
class RecordPunch
{
    public function __construct(private ClockRestrictions $restrictions) {}

    /**
     * @param  'main'|'break'|TimeLogType  $action  main button, break button or an explicit type
     *
     * @throws ValidationException
     */
    public function handle(Employee $employee, string|TimeLogType $action, ?string $ip, ?float $latitude, ?float $longitude, ?int $userId, TimeLogSource $source = TimeLogSource::Web): TimeLog
    {
        $check = $this->restrictions->check($employee->branch, $ip, $latitude, $longitude);

        if (! $check['allowed']) {
            throw ValidationException::withMessages(['punch' => $check['reason']]);
        }

        $type = match (true) {
            $action instanceof TimeLogType => $action,
            $action === 'break' => $this->breakAction($employee),
            default => $this->nextType($employee),
        };

        if ($type === null) {
            throw ValidationException::withMessages(['punch' => 'Clock in before taking a break.']);
        }

        $recent = TimeLog::query()
            ->where('employee_id', $employee->id)
            ->where('type', $type)
            ->where('logged_at', '>=', now()->subMinute())
            ->exists();

        if ($recent) {
            throw ValidationException::withMessages(['punch' => 'You just punched. Please wait a minute before trying again.']);
        }

        return TimeLog::query()->create([
            'employee_id' => $employee->id,
            'logged_at' => now(),
            'type' => $type,
            'source' => $source,
            'ip_address' => $ip,
            'distance_m' => $check['distance_m'],
            'created_by' => $userId,
        ]);
    }

    /**
     * Main button: in → out, ending a break first when on break.
     */
    public function nextType(Employee $employee): TimeLogType
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
    public function breakAction(Employee $employee): ?TimeLogType
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
