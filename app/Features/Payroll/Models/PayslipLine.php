<?php

namespace App\Features\Payroll\Models;

use App\Shared\Money\Money;
use App\Shared\Money\MoneyCast;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $payslip_id
 * @property string $kind
 * @property string $code
 * @property string $label
 * @property string|null $quantity
 * @property string|null $unit
 * @property Money $amount
 * @property bool $taxable
 * @property int $sort
 */
#[Fillable(['payslip_id', 'kind', 'code', 'label', 'quantity', 'unit', 'amount', 'taxable', 'sort'])]
class PayslipLine extends Model
{
    public $timestamps = false;

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return ['amount' => MoneyCast::class, 'taxable' => 'boolean'];
    }
}
