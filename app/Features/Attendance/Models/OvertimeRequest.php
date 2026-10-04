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
 * Overtime has to be approved before it is paid (see
 * hris.attendance.overtime_requires_approval). Shares the request statuses
 * of leave requests.
 *
 * @property int $id
 * @property int $employee_id
 * @property Carbon $date
 * @property int $minutes
 * @property string $reason
 * @property LeaveStatus $status
 * @property int|null $reviewed_by
 * @property Carbon|null $reviewed_at
 * @property string|null $review_remarks
 * @property-read Employee $employee
 * @property-read User|null $reviewer
 */
#[Fillable(['employee_id', 'date', 'minutes', 'reason', 'status', 'reviewed_by', 'reviewed_at', 'review_remarks'])]
class OvertimeRequest extends Model
{
    use Auditable;

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'minutes' => 'integer',
            'reviewed_at' => 'datetime',
            'status' => LeaveStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
