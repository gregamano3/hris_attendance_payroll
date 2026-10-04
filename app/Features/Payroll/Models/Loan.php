<?php

namespace App\Features\Payroll\Models;

use App\Features\Employees\Models\Employee;
use App\Features\Payroll\Enums\LoanStatus;
use App\Features\Payroll\Enums\LoanType;
use App\Shared\Money\Money;
use App\Shared\Money\MoneyCast;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Amortized through payroll: the balance only goes down when a payroll run
 * containing the deduction is finalized.
 *
 * @property int $id
 * @property int $employee_id
 * @property LoanType $type
 * @property string|null $reference_no
 * @property Money $principal
 * @property Money $amortization
 * @property Money $balance
 * @property Carbon $starts_on
 * @property LoanStatus $status
 * @property string|null $notes
 * @property-read Employee $employee
 */
#[Fillable(['employee_id', 'type', 'reference_no', 'principal', 'amortization', 'balance', 'starts_on', 'status', 'notes'])]
class Loan extends Model
{
    public const LINE_PREFIX = 'LOAN_';

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'type' => LoanType::class,
            'status' => LoanStatus::class,
            'principal' => MoneyCast::class,
            'amortization' => MoneyCast::class,
            'balance' => MoneyCast::class,
            'starts_on' => 'date',
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
     * @return HasMany<LoanPayment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(LoanPayment::class);
    }

    /**
     * Amount deducted on the next run: the amortization, capped at the balance.
     */
    public function nextDeduction(): Money
    {
        return $this->amortization->min($this->balance)->max(Money::zero());
    }

    public function lineCode(): string
    {
        return self::LINE_PREFIX.$this->id;
    }
}
