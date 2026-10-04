<?php

namespace App\Features\Attendance\Models;

use App\Features\Attendance\Enums\LeaveStatus;
use App\Features\Employees\Models\Employee;
use App\Models\User;
use App\Shared\Audit\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $employee_id
 * @property int $leave_type_id
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property string $day_part full | am | pm
 * @property string $days
 * @property string|null $reason
 * @property LeaveStatus $status
 * @property int|null $reviewed_by
 * @property Carbon|null $reviewed_at
 * @property string|null $review_remarks
 * @property-read Employee $employee
 * @property-read LeaveType $leaveType
 * @property-read User|null $reviewer
 */
#[Fillable(['employee_id', 'leave_type_id', 'start_date', 'end_date', 'day_part', 'days', 'reason', 'status', 'reviewed_by', 'reviewed_at', 'review_remarks'])]
class LeaveRequest extends Model
{
    use Auditable;

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'reviewed_at' => 'datetime',
            'status' => LeaveStatus::class,
        ];
    }

    public function dayPartLabel(): string
    {
        return match ($this->day_part) {
            'am' => 'Morning',
            'pm' => 'Afternoon',
            default => 'Whole day',
        };
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }

    /**
     * @return BelongsTo<LeaveType, $this>
     */
    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
