<?php

namespace App\Features\Attendance\Compute;

use App\Features\Attendance\Enums\AttendanceStatus;
use App\Features\Attendance\Enums\HolidayType;
use Illuminate\Support\Carbon;

final readonly class DayResult
{
    public function __construct(
        public AttendanceStatus $status,
        public ?Carbon $timeIn = null,
        public ?Carbon $timeOut = null,
        public int $workedMinutes = 0,
        public int $lateMinutes = 0,
        public int $undertimeMinutes = 0,
        public int $overtimeMinutes = 0,
        public int $nightDiffMinutes = 0,
        public bool $isRestDay = false,
        public ?HolidayType $holiday = null,
        public ?int $leaveRequestId = null,
        public float $leaveFraction = 0.0,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        return [
            'status' => $this->status,
            'time_in' => $this->timeIn,
            'time_out' => $this->timeOut,
            'worked_minutes' => $this->workedMinutes,
            'late_minutes' => $this->lateMinutes,
            'undertime_minutes' => $this->undertimeMinutes,
            'overtime_minutes' => $this->overtimeMinutes,
            'night_diff_minutes' => $this->nightDiffMinutes,
            'is_rest_day' => $this->isRestDay,
            'holiday_type' => $this->holiday,
            'leave_request_id' => $this->leaveRequestId,
            'leave_fraction' => $this->leaveFraction,
        ];
    }
}
