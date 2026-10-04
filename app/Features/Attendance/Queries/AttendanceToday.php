<?php

namespace App\Features\Attendance\Queries;

use App\Features\Attendance\Enums\LeaveStatus;
use App\Features\Attendance\Enums\TimeLogType;
use App\Features\Attendance\Models\LeaveRequest;
use App\Features\Attendance\Models\TimeLog;

/**
 * Lightweight counters for the dashboard.
 */
class AttendanceToday
{
    public function clockedIn(): int
    {
        return TimeLog::query()
            ->where('type', TimeLogType::In)
            ->whereDate('logged_at', today())
            ->distinct()
            ->count('employee_id');
    }

    public function pendingLeaves(): int
    {
        return LeaveRequest::query()->where('status', LeaveStatus::Pending)->count();
    }
}
