<?php

namespace App\Features\Attendance\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Leave credits earned in a year for accruing leave types.
 *
 * @property int $id
 * @property int $employee_id
 * @property int $leave_type_id
 * @property int $year
 * @property string $earned
 * @property string $carried_over
 * @property int $accrued_through_month
 * @property-read LeaveType $leaveType
 */
#[Fillable(['employee_id', 'leave_type_id', 'year', 'earned', 'carried_over', 'accrued_through_month'])]
class LeaveCredit extends Model
{
    /**
     * @return BelongsTo<LeaveType, $this>
     */
    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function total(): float
    {
        return (float) $this->earned + (float) $this->carried_over;
    }
}
