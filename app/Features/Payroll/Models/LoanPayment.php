<?php

namespace App\Features\Payroll\Models;

use App\Shared\Money\Money;
use App\Shared\Money\MoneyCast;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $loan_id
 * @property int|null $payroll_run_id
 * @property int|null $final_pay_id
 * @property Money $amount
 * @property Money $balance_after
 * @property-read PayrollRun|null $run
 */
#[Fillable(['loan_id', 'payroll_run_id', 'final_pay_id', 'amount', 'balance_after'])]
class LoanPayment extends Model
{
    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return ['amount' => MoneyCast::class, 'balance_after' => MoneyCast::class];
    }

    /**
     * @return BelongsTo<PayrollRun, $this>
     */
    public function run(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class, 'payroll_run_id');
    }
}
