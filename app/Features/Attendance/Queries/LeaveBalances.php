<?php

namespace App\Features\Attendance\Queries;

use App\Features\Attendance\Enums\LeaveStatus;
use App\Features\Attendance\Models\LeaveRequest;
use App\Features\Attendance\Models\LeaveType;
use App\Features\Attendance\Models\Shift;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class LeaveBalances
{
    public function __construct(
        private ShiftResolver $shifts,
        private HolidayCalendar $holidays,
    ) {}

    /**
     * Per leave type: allowance, days used (approved + pending) and remaining.
     *
     * @return Collection<int, array{type: LeaveType, allowance: int, used: float, remaining: float|null}>
     */
    public function forEmployee(int $employeeId, int $year): Collection
    {
        $used = LeaveRequest::query()
            ->where('employee_id', $employeeId)
            ->whereIn('status', [LeaveStatus::Approved, LeaveStatus::Pending])
            ->whereYear('start_date', $year)
            ->selectRaw('leave_type_id, sum(days) as total')
            ->groupBy('leave_type_id')
            ->pluck('total', 'leave_type_id');

        return LeaveType::query()->orderBy('name')->get()->map(fn (LeaveType $type) => [
            'type' => $type,
            'allowance' => $type->days_per_year,
            'used' => (float) ($used[$type->id] ?? 0),
            'remaining' => $type->hasYearlyCap() ? $type->days_per_year - (float) ($used[$type->id] ?? 0) : null,
        ]);
    }

    /**
     * Working days in the range for the employee (rest days and holidays excluded).
     */
    public function workingDays(int $employeeId, Carbon $from, Carbon $to): int
    {
        $days = 0;

        for ($date = $from->copy()->startOfDay(); $date->lte($to); $date->addDay()) {
            $shift = $this->shifts->forEmployee($employeeId, $date);

            if ($this->isWorkingDay($shift, $date)) {
                $days++;
            }
        }

        return $days;
    }

    private function isWorkingDay(Shift $shift, Carbon $date): bool
    {
        return $shift->isWorkDay($date) && $this->holidays->typeOn($date) === null;
    }
}
