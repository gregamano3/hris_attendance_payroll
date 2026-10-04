<?php

namespace App\Features\Payroll\Models;

use App\Shared\Audit\Auditable;
use App\Shared\Money\Money;
use App\Shared\Money\MoneyCast;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * De minimis benefit with its tax-exempt ceiling; amounts above it are taxable.
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property Money $limit_amount
 * @property string $period monthly | annual
 */
#[Fillable(['code', 'name', 'limit_amount', 'period'])]
class DeMinimisBenefit extends Model
{
    use Auditable;

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return ['limit_amount' => MoneyCast::class];
    }

    public function lineCode(): string
    {
        return 'DM_'.$this->code;
    }
}
