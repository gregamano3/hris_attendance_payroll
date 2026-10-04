<?php

namespace App\Features\Payroll\Models;

use App\Shared\Money\Money;
use App\Shared\Money\MoneyCast;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property Carbon $effective_from
 * @property string $frequency
 * @property Money $lower_bound
 * @property Money|null $upper_bound
 * @property Money $base_tax
 * @property string $rate
 */
#[Fillable(['effective_from', 'frequency', 'lower_bound', 'upper_bound', 'base_tax', 'rate'])]
class TaxBracket extends Model
{
    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'lower_bound' => MoneyCast::class,
            'upper_bound' => MoneyCast::class,
            'base_tax' => MoneyCast::class,
        ];
    }
}
