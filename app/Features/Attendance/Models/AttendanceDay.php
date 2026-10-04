<?php

namespace App\Features\Attendance\Models;

use App\Features\Attendance\Enums\AttendanceStatus;
use App\Features\Attendance\Enums\HolidayType;
use App\Features\Employees\Models\Employee;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Computed attendance for one employee on one date (derived from time logs,
 * shift, holidays and approved leaves; never edited by hand).
 *
 * @property int $id
 * @property int $employee_id
 * @property Carbon $date
 * @property int|null $shift_id
 * @property AttendanceStatus $status
 * @property Carbon|null $time_in
 * @property Carbon|null $time_out
 * @property int $worked_minutes
 * @property int $late_minutes
 * @property int $undertime_minutes
 * @property int $overtime_minutes
 * @property int $night_diff_minutes
 * @property bool $is_rest_day
 * @property HolidayType|null $holiday_type
 * @property int|null $leave_request_id
 * @property string $leave_fraction
 * @property-read Shift|null $shift
 * @property-read Employee $employee
 * @property-read LeaveRequest|null $leaveRequest
 */
#[Fillable([
    'employee_id', 'date', 'shift_id', 'status', 'time_in', 'time_out', 'worked_minutes', 'late_minutes',
    'undertime_minutes', 'overtime_minutes', 'night_diff_minutes', 'is_rest_day', 'holiday_type', 'leave_request_id', 'leave_fraction',
])]
class AttendanceDay extends Model
{
    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'time_in' => 'datetime',
            'time_out' => 'datetime',
            'status' => AttendanceStatus::class,
            'holiday_type' => HolidayType::class,
            'is_rest_day' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Shift, $this>
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }

    /**
     * @return BelongsTo<LeaveRequest, $this>
     */
    public function leaveRequest(): BelongsTo
    {
        return $this->belongsTo(LeaveRequest::class);
    }
}
