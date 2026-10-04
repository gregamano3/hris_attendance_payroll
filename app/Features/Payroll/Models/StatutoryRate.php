<?php

namespace App\Features\Payroll\Models;

use App\Features\Payroll\Enums\StatutoryScheme;
use App\Shared\Audit\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property StatutoryScheme $scheme
 * @property Carbon $effective_from
 * @property array<string, int|float|string> $parameters
 */
#[Fillable(['scheme', 'effective_from', 'parameters'])]
class StatutoryRate extends Model
{
    use Auditable;

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'scheme' => StatutoryScheme::class,
            'effective_from' => 'date',
            'parameters' => 'array',
        ];
    }
}
