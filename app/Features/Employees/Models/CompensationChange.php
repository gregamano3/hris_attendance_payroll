<?php

namespace App\Features\Employees\Models;

use App\Features\Employees\Enums\RateType;
use App\Models\User;
use App\Shared\Audit\Auditable;
use App\Shared\Money\Money;
use App\Shared\Money\MoneyCast;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Effective-dated salary rate. The employee's basic_rate / rate_type always
 * mirror the latest change effective today.
 *
 * @property int $id
 * @property int $employee_id
 * @property Carbon $effective_from
 * @property RateType $rate_type
 * @property Money $basic_rate
 * @property string|null $reason
 * @property int|null $created_by
 * @property-read Employee $employee
 * @property-read User|null $creator
 */
#[Fillable(['employee_id', 'effective_from', 'rate_type', 'basic_rate', 'reason', 'created_by'])]
class CompensationChange extends Model
{
    use Auditable;

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return ['effective_from' => 'date', 'rate_type' => RateType::class, 'basic_rate' => MoneyCast::class];
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
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
