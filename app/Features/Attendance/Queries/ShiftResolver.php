<?php

namespace App\Features\Attendance\Queries;

use App\Features\Attendance\Models\EmployeeShift;
use App\Features\Attendance\Models\Shift;
use Illuminate\Support\Carbon;

/**
 * Finds the shift an employee works on a date: the latest assignment
 * effective on or before that date, else the default shift, else a standard
 * 8:00–17:00 Monday to Friday schedule.
 */
class ShiftResolver
{
    public function forEmployee(int $employeeId, Carbon $date): Shift
    {
        $assignment = EmployeeShift::query()
            ->with('shift')
            ->where('employee_id', $employeeId)
            ->whereDate('effective_from', '<=', $date)
            ->latest('effective_from')
            ->first();

        return $assignment->shift
            ?? Shift::query()->where('is_default', true)->first()
            ?? new Shift([
                'name' => 'Standard', 'start_time' => '08:00:00', 'end_time' => '17:00:00',
                'break_minutes' => 60, 'grace_minutes' => 0, 'work_days' => [1, 2, 3, 4, 5],
            ]);
    }
}
