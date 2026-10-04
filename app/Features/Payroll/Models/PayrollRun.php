<?php

namespace App\Features\Payroll\Models;

use App\Features\Payroll\Enums\PayrollRunStatus;
use App\Features\Payroll\Enums\PayrollRunType;
use App\Models\User;
use App\Shared\Audit\Auditable;
use App\Shared\Money\Money;
use App\Shared\Money\MoneyCast;
use App\Shared\Period;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property PayrollRunType $type
 * @property Carbon $period_start
 * @property Carbon $period_end
 * @property Carbon $pay_date
 * @property PayrollRunStatus $status
 * @property int $progress
 * @property string|null $compute_error
 * @property int $employee_count
 * @property Money $total_gross
 * @property Money $total_deductions
 * @property Money $total_net
 * @property Money $total_employer
 * @property string|null $notes
 * @property int|null $created_by
 * @property Carbon|null $computed_at
 * @property int|null $finalized_by
 * @property Carbon|null $finalized_at
 * @property-read User|null $finalizer
 */
#[Fillable([
    'name', 'type', 'period_start', 'period_end', 'pay_date', 'status', 'progress', 'compute_error', 'employee_count', 'total_gross', 'total_deductions',
    'total_net', 'total_employer', 'notes', 'created_by', 'computed_at', 'finalized_by', 'finalized_at',
])]
class PayrollRun extends Model
{
    use Auditable;

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'pay_date' => 'date',
            'status' => PayrollRunStatus::class,
            'type' => PayrollRunType::class,
            'total_gross' => MoneyCast::class,
            'total_deductions' => MoneyCast::class,
            'total_net' => MoneyCast::class,
            'total_employer' => MoneyCast::class,
            'computed_at' => 'datetime',
            'finalized_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<Payslip, $this>
     */
    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    /**
     * @return HasMany<PayrollAdjustment, $this>
     */
    public function adjustments(): HasMany
    {
        return $this->hasMany(PayrollAdjustment::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function finalizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    public function period(): Period
    {
        return new Period($this->period_start, $this->period_end);
    }

    public function isComputing(): bool
    {
        return $this->status === PayrollRunStatus::Computing;
    }

    public function isThirteenthMonth(): bool
    {
        return $this->type === PayrollRunType::ThirteenthMonth;
    }

    public function isLocked(): bool
    {
        return $this->status->isLocked();
    }
}
