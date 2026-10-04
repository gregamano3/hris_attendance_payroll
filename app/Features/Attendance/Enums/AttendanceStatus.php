<?php

namespace App\Features\Attendance\Enums;

enum AttendanceStatus: string
{
    case Present = 'present';
    case Absent = 'absent';
    case Incomplete = 'incomplete';
    case RestDay = 'rest_day';
    case Holiday = 'holiday';
    case OnLeave = 'on_leave';
    case Upcoming = 'upcoming';

    public function label(): string
    {
        return match ($this) {
            self::Present => 'Present',
            self::Absent => 'Absent',
            self::Incomplete => 'Incomplete logs',
            self::RestDay => 'Rest day',
            self::Holiday => 'Holiday',
            self::OnLeave => 'On leave',
            self::Upcoming => 'Upcoming',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Present => 'success',
            self::Absent => 'danger',
            self::Incomplete => 'warning',
            self::RestDay, self::Upcoming => 'secondary',
            self::Holiday => 'info',
            self::OnLeave => 'primary',
        };
    }
}
