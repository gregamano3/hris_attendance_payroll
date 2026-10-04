<?php

namespace App\Features\Payroll\Models;

use App\Features\Employees\Models\Employee;
use App\Features\Payroll\Enums\TaxTreatment;
use App\Shared\Audit\Auditable;
use App\Shared\Money\Money;
use App\Shared\Money\MoneyCast;
use App\Shared\Period;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $employee_id
 * @property string $label
 * @property Money $amount
 * @property TaxTreatment $tax_treatment
 * @property int|null $de_minimis_benefit_id
 * @property-read DeMinimisBenefit|null $deMinimisBenefit
 * @property Carbon $starts_on
 * @property Carbon|null $ends_on
 * @property-read Employee $employee
 */
#[Fillable(['employee_id', 'label', 'amount', 'tax_treatment', 'de_minimis_benefit_id', 'starts_on', 'ends_on'])]
class RecurringEarning extends Model
{
    use Auditable;

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'amount' => MoneyCast::class,
            'tax_treatment' => TaxTreatment::class,
            'starts_on' => 'date',
            'ends_on' => 'date',
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
     * @return BelongsTo<DeMinimisBenefit, $this>
     */
    public function deMinimisBenefit(): BelongsTo
    {
        return $this->belongsTo(DeMinimisBenefit::class);
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function activeDuring(Builder $query, Period $period): void
    {
        $query->whereDate('starts_on', '<=', $period->to)
            ->where(fn (Builder $q) => $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', $period->from));
    }
}
