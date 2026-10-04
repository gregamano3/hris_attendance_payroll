<?php

namespace App\Features\Attendance\Compute;

use App\Features\Attendance\Enums\LeaveStatus;
use App\Features\Attendance\Models\LeaveCredit;
use App\Features\Attendance\Models\LeaveRequest;
use App\Features\Attendance\Models\LeaveType;
use App\Features\Employees\Models\Employee;
use Illuminate\Support\Carbon;

/**
 * Monthly leave accrual. Idempotent: each employee/type/year remembers the
 * last month accrued, so running it again never double-credits.
 *
 * - A month is earned once it has ended, and only for months on or after the
 *   hire month (the hire month counts when hired on the 1st–15th).
 * - On the first accrual of a year, unused credits of the previous year are
 *   carried over up to the leave type's cap.
 */
class AccrueLeaveCredits
{
    /**
     * Accrue every completed month of the year up to and including $through.
     */
    public function handle(Carbon $through): int
    {
        $types = LeaveType::query()->where('accrual_per_month', '>', 0)->get();
        $count = 0;

        if ($types->isEmpty()) {
            return 0;
        }

        Employee::query()->active()->each(function (Employee $employee) use ($types, $through, &$count) {
            foreach ($types as $type) {
                $count += $this->accrue($employee, $type, $through) ? 1 : 0;
            }
        });

        return $count;
    }

    public function accrue(Employee $employee, LeaveType $type, Carbon $through): bool
    {
        $year = $through->year;
        $credit = LeaveCredit::query()->firstOrNew([
            'employee_id' => $employee->id,
            'leave_type_id' => $type->id,
            'year' => $year,
        ]);

        if (! $credit->exists) {
            $credit->carried_over = (string) $this->carryOver($employee, $type, $year);
        }

        $firstMonth = $employee->hired_at->year === $year
            ? $employee->hired_at->month + ($employee->hired_at->day > 15 ? 1 : 0)
            : 1;
        $months = max(0, $through->month - max($firstMonth, $credit->accrued_through_month + 1) + 1);

        if ($months === 0 && $credit->exists) {
            return false;
        }

        $credit->earned = (string) ((float) $credit->earned + $months * (float) $type->accrual_per_month);
        $credit->accrued_through_month = max($credit->accrued_through_month, $through->month);
        $credit->save();

        return $months > 0;
    }

    private function carryOver(Employee $employee, LeaveType $type, int $year): float
    {
        if ($type->carry_over_cap === 0) {
            return 0;
        }

        $previous = LeaveCredit::query()
            ->where(['employee_id' => $employee->id, 'leave_type_id' => $type->id, 'year' => $year - 1])
            ->first();

        if ($previous === null) {
            return 0;
        }

        $used = (float) LeaveRequest::query()
            ->where(['employee_id' => $employee->id, 'leave_type_id' => $type->id, 'status' => LeaveStatus::Approved])
            ->whereYear('start_date', $year - 1)
            ->sum('days');

        return min($type->carry_over_cap, max(0, $previous->total() - $used));
    }
}
