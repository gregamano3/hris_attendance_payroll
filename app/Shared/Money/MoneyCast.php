<?php

namespace App\Shared\Money;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Casts an integer centavos column to a Money value object.
 *
 * @implements CastsAttributes<Money|null, Money|string|int|float|null>
 */
class MoneyCast implements CastsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Money
    {
        return $value === null ? null : Money::ofCentavos((int) $value);
    }

    /**
     * Money instances are stored as-is; scalars are interpreted as pesos.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?int
    {
        return match (true) {
            $value === null || $value === '' => null,
            $value instanceof Money => $value->centavos,
            default => Money::ofPesos($value)->centavos,
        };
    }
}
