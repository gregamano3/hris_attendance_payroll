<?php

namespace App\Features\Payroll\Models;

use App\Features\Employees\Models\Employee;
use App\Features\Payroll\Enums\PayrollRunStatus;
use App\Models\User;
use App\Shared\Money\Money;
use App\Shared\Money\MoneyCast;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $employee_id
 * @property Carbon $separation_date
 * @property PayrollRunStatus $status
 * @property Money $total_earnings
 * @property Money $total_deductions
 * @property Money $net_pay
 * @property list<array{kind: string, code: string, label: string, quantity: float|null, unit: string|null, amount: string, taxable: bool}>|null $lines
 * @property array<string, string|float>|null $details
 * @property Carbon|null $computed_at
 * @property int|null $finalized_by
 * @property Carbon|null $finalized_at
 * @property-read Employee $employee
 * @property-read User|null $finalizer
 */
#[Fillable([
    'employee_id', 'separation_date', 'status', 'total_earnings', 'total_deductions', 'net_pay', 'lines', 'details',
    'computed_at', 'finalized_by', 'finalized_at',
])]
class FinalPay extends Model
{
    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'separation_date' => 'date',
            'status' => PayrollRunStatus::class,
            'total_earnings' => MoneyCast::class,
            'total_deductions' => MoneyCast::class,
            'net_pay' => MoneyCast::class,
            'lines' => 'array',
            'details' => 'array',
            'computed_at' => 'datetime',
            'finalized_at' => 'datetime',
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
    public function finalizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    /**
     * @return HasMany<FinalPayAdjustment, $this>
     */
    public function adjustments(): HasMany
    {
        return $this->hasMany(FinalPayAdjustment::class);
    }

    public function isLocked(): bool
    {
        return $this->status === PayrollRunStatus::Finalized;
    }
}
