<?php

namespace App\Features\Payroll\Compute;

use App\Shared\Money\Money;

/**
 * Pag-IBIG (HDMF) contributions on the monthly compensation, capped at the
 * maximum fund salary.
 *
 * Parameters: ee_rate, ee_rate_low, low_threshold, er_rate, max_base
 */
final readonly class PagIbigCalculator
{
    /**
     * @param  array<string, int|float|string>  $parameters
     */
    public function __construct(private array $parameters) {}

    /**
     * @return array{employee: Money, employer: Money}
     */
    public function monthly(Money $compensation): array
    {
        $base = $compensation->min(Money::ofPesos($this->parameters['max_base']));
        $eeRate = $compensation->isGreaterThan(Money::ofPesos($this->parameters['low_threshold']))
            ? $this->parameters['ee_rate']
            : $this->parameters['ee_rate_low'];

        return [
            'employee' => $base->multipliedBy($eeRate),
            'employer' => $base->multipliedBy($this->parameters['er_rate']),
        ];
    }
}
