<?php

namespace App\Features\Payroll\Models;

use App\Features\Employees\Models\Employee;
use App\Shared\Audit\Auditable;
use App\Shared\Money\Money;
use App\Shared\Money\MoneyCast;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $payroll_run_id
 * @property int $employee_id
 * @property string $kind
 * @property string|null $code
 * @property int|null $source_run_id
 * @property string $label
 * @property Money $amount
 * @property bool $taxable
 * @property-read Employee $employee
 */
#[Fillable(['payroll_run_id', 'employee_id', 'kind', 'code', 'source_run_id', 'label', 'amount', 'taxable', 'created_by'])]
class PayrollAdjustment extends Model
{
    use Auditable;

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return ['amount' => MoneyCast::class, 'taxable' => 'boolean'];
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }
}
