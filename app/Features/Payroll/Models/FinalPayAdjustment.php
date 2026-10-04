<?php

namespace App\Features\Payroll\Models;

use App\Shared\Money\Money;
use App\Shared\Money\MoneyCast;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $final_pay_id
 * @property string $kind
 * @property string $label
 * @property Money $amount
 * @property bool $taxable
 */
#[Fillable(['final_pay_id', 'kind', 'label', 'amount', 'taxable'])]
class FinalPayAdjustment extends Model
{
    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return ['amount' => MoneyCast::class, 'taxable' => 'boolean'];
    }
}
