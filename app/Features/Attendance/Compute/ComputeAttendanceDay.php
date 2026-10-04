<?php

namespace App\Features\Attendance\Compute;

use App\Features\Attendance\Enums\AttendanceStatus;
use App\Features\Attendance\Enums\HolidayType;
use App\Features\Attendance\Enums\LeaveStatus;
use App\Features\Attendance\Enums\TimeLogType;
use App\Features\Attendance\Models\AttendanceDay;
use App\Features\Attendance\Models\LeaveRequest;
use App\Features\Attendance\Models\OvertimeRequest;
use App\Features\Attendance\Models\TimeLog;
use App\Features\Attendance\Queries\HolidayCalendar;
use App\Features\Attendance\Queries\ShiftResolver;
use App\Features\Employees\Models\Employee;
use Illuminate\Support\Carbon;

/**
 * Loads everything needed for one employee-day, runs the calculator and
 * stores the result in attendance_days.
 */
class ComputeAttendanceDay
{
    public function __construct(
        private ShiftResolver $shifts,
        private HolidayCalendar $holidays,
    ) {}

    public function handle(int $employeeId, Carbon $date, bool $lookAhead = true): AttendanceDay
    {
        $date = $date->copy()->startOfDay();
        $shift = $this->shifts->forEmployee($employeeId, $date);

        $windowStart = $shift->startsAt($date)->subMinutes((int) config('hris.attendance.log_window_before_minutes', 240));
        $windowEnd = $shift->endsAt($date)->addMinutes((int) config('hris.attendance.log_window_after_minutes', 480));

        $logs = TimeLog::query()
            ->where('employee_id', $employeeId)
            ->whereBetween('logged_at', [$windowStart, $windowEnd])
            ->orderBy('logged_at')
            ->get();

        $leave = LeaveRequest::query()
            ->where('employee_id', $employeeId)
            ->where('status', LeaveStatus::Approved)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->first(['id', 'day_part']);

        $approvedOvertime = config('hris.attendance.overtime_requires_approval')
            ? (int) OvertimeRequest::query()
                ->where('employee_id', $employeeId)
                ->whereDate('date', $date)
                ->where('status', LeaveStatus::Approved)
                ->sum('minutes')
            : null;

        $result = AttendanceCalculator::fromConfig()->compute(new DayInput(
            date: $date,
            shift: $shift,
            timeIns: $logs->where('type', TimeLogType::In)->pluck('logged_at')->values()->all(),
            timeOuts: $logs->where('type', TimeLogType::Out)->pluck('logged_at')->values()->all(),
            holiday: $this->holidays->typeOn($date),
            leaveRequestId: $leave?->id,
            approvedOvertimeMinutes: $approvedOvertime,
            halfDayLeave: $leave !== null && $leave->day_part !== 'full' ? $leave->day_part : null,
        ));

        $isUnworkedRegularHoliday = $result->status === AttendanceStatus::Holiday
            && $result->holiday === HolidayType::Regular
            && ! $result->isRestDay;

        $day = AttendanceDay::query()->updateOrCreate(
            ['employee_id' => $employeeId, 'date' => $date->toDateString()],
            [
                ...$result->toAttributes(),
                'shift_id' => $shift->id,
                'holiday_pay_eligible' => $isUnworkedRegularHoliday ? $this->holidayPayEligible($employeeId, $date) : null,
            ],
        );

        // A change on a work day can affect the eligibility of the holidays right after it.
        if ($lookAhead && ! in_array($result->status, [AttendanceStatus::Holiday, AttendanceStatus::RestDay], true)) {
            $this->refreshFollowingHolidays($employeeId, $date);
        }

        return $day;
    }

    private function holidayPayEligible(int $employeeId, Carbon $holiday): bool
    {
        if (! config('hris.payroll.holiday_eligibility', true)) {
            return true;
        }

        $hiredAt = Employee::withTrashed()->whereKey($employeeId)->value('hired_at');

        for ($date = $holiday->copy()->subDay(), $i = 0; $i < 14; $date->subDay(), $i++) {
            if ($hiredAt !== null && $date->lt(Carbon::parse($hiredAt))) {
                return true; // no work day before the holiday since hire
            }

            if (! $this->shifts->forEmployee($employeeId, $date)->isWorkDay($date) || $this->holidays->typeOn($date) !== null) {
                continue;
            }

            $previous = AttendanceDay::query()->with('leaveRequest.leaveType')
                ->where('employee_id', $employeeId)->whereDate('date', $date)->first()
                ?? $this->handle($employeeId, $date, lookAhead: false)->load('leaveRequest.leaveType');

            return HolidayPayRule::isEligible($previous->status, (bool) $previous->leaveRequest?->leaveType->is_paid);
        }

        return true;
    }

    private function refreshFollowingHolidays(int $employeeId, Carbon $date): void
    {
        for ($next = $date->copy()->addDay(), $i = 0; $i < 7; $next->addDay(), $i++) {
            $isHoliday = $this->holidays->typeOn($next) !== null;

            if (! $isHoliday && $this->shifts->forEmployee($employeeId, $next)->isWorkDay($next)) {
                return; // reached the next work day
            }

            if ($isHoliday && AttendanceDay::query()->where('employee_id', $employeeId)->whereDate('date', $next)->exists()) {
                $this->handle($employeeId, $next, lookAhead: false);
            }
        }
    }

    /**
     * Recompute every day of a period (inclusive).
     */
    public function handleRange(int $employeeId, Carbon $from, Carbon $to): void
    {
        for ($date = $from->copy()->startOfDay(); $date->lte($to); $date->addDay()) {
            $this->handle($employeeId, $date);
        }
    }
}
