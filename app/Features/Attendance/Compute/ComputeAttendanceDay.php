<?php

namespace App\Features\Attendance\Compute;

use App\Features\Attendance\Enums\LeaveStatus;
use App\Features\Attendance\Enums\TimeLogType;
use App\Features\Attendance\Models\AttendanceDay;
use App\Features\Attendance\Models\LeaveRequest;
use App\Features\Attendance\Models\OvertimeRequest;
use App\Features\Attendance\Models\TimeLog;
use App\Features\Attendance\Queries\HolidayCalendar;
use App\Features\Attendance\Queries\ShiftResolver;
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

    public function handle(int $employeeId, Carbon $date): AttendanceDay
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

        $leaveId = LeaveRequest::query()
            ->where('employee_id', $employeeId)
            ->where('status', LeaveStatus::Approved)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->value('id');

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
            leaveRequestId: $leaveId,
            approvedOvertimeMinutes: $approvedOvertime,
        ));

        return AttendanceDay::query()->updateOrCreate(
            ['employee_id' => $employeeId, 'date' => $date->toDateString()],
            [...$result->toAttributes(), 'shift_id' => $shift->id],
        );
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
