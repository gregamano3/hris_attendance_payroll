<?php

namespace App\Features\Attendance\Compute;

use App\Features\Attendance\Enums\AttendanceStatus;

/**
 * Labor Code IRR (Book III, Rule IV, Sec. 6): an employee is entitled to pay
 * for an unworked regular holiday only if present, or on leave with pay, on
 * the work day immediately preceding the holiday.
 */
final class HolidayPayRule
{
    /**
     * @param  AttendanceStatus|null  $previousWorkDay  status of the preceding work day (null = unknown / before hire)
     */
    public static function isEligible(?AttendanceStatus $previousWorkDay, bool $onPaidLeave = false): bool
    {
        return match ($previousWorkDay) {
            null, AttendanceStatus::Present, AttendanceStatus::Incomplete, AttendanceStatus::Upcoming => true,
            AttendanceStatus::OnLeave => $onPaidLeave,
            default => false,
        };
    }
}
