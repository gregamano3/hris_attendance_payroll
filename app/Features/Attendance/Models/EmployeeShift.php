<?php

namespace App\Features\Attendance\Models;

use App\Features\Employees\Models\Employee;
use App\Shared\Audit\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Effective-dated shift assignment.
 *
 * @property int $id
 * @property int $employee_id
 * @property int $shift_id
 * @property Carbon $effective_from
 * @property-read Shift $shift
 * @property-read Employee $employee
 */
#[Fillable(['employee_id', 'shift_id', 'effective_from'])]
class EmployeeShift extends Model
{
    use Auditable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['effective_from' => 'date'];
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
        return $this->belongsTo(Employee::class);
    }
}
