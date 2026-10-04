<?php

namespace App\Features\Attendance\Compute;

use App\Features\Attendance\Enums\HolidayType;
use App\Features\Attendance\Models\Shift;
use Illuminate\Support\Carbon;

/**
 * Everything the calculator needs to compute one attendance day.
 */
final readonly class DayInput
{
    /**
     * @param  list<Carbon>  $timeIns  punches of type "in" within the day's window
     * @param  list<Carbon>  $timeOuts  punches of type "out" within the day's window
     */
    public function __construct(
        public Carbon $date,
        public Shift $shift,
        public array $timeIns = [],
        public array $timeOuts = [],
        public ?HolidayType $holiday = null,
        public ?int $leaveRequestId = null,
        public ?Carbon $now = null,
        public ?int $approvedOvertimeMinutes = null, // null = overtime needs no approval
        public ?string $halfDayLeave = null, // am | pm: half of the shift is on leave
    ) {}
}
