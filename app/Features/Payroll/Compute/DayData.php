<?php

namespace App\Features\Payroll\Compute;

/**
 * Attendance facts of one day as needed by payroll (decoupled from the
 * Attendance models).
 */
final readonly class DayData
{
    public const PRESENT = 'present';

    public const ABSENT = 'absent';

    public const INCOMPLETE = 'incomplete';

    public const HOLIDAY = 'holiday';

    public const LEAVE = 'on_leave';

    public const REST_DAY = 'rest_day';

    public function __construct(
        public string $date,
        public string $status,
        public bool $isRestDay = false,
        public ?string $holiday = null, // null | regular | special_non_working
        public int $workedMinutes = 0,
        public int $lateMinutes = 0,
        public int $undertimeMinutes = 0,
        public int $overtimeMinutes = 0,
        public int $nightDiffMinutes = 0,
        public bool $paidLeave = false,
    ) {}

    /**
     * regular | rest_day | special | special_rest | regular_holiday | regular_holiday_rest
     */
    public function dayType(): string
    {
        $base = match ($this->holiday) {
            'regular' => 'regular_holiday',
            'special_non_working' => 'special',
            default => $this->isRestDay ? 'rest_day' : 'regular',
        };

        return $this->isRestDay && $this->holiday !== null ? "{$base}_rest" : $base;
    }
}
