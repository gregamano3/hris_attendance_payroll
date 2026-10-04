<?php

namespace App\Features\Payroll\Compute;

use App\Shared\Money\Money;

/**
 * SSS contribution from the monthly salary credit (MSC).
 *
 * Parameters (pesos / fractions):
 *   ee_rate, er_rate          employee / employer share of the MSC
 *   msc_min, msc_max, msc_step  salary credit brackets (e.g. 5,000–35,000 by 500)
 *   ec_threshold, ec_low, ec_high  Employees' Compensation paid by the employer
 */
final readonly class SssCalculator
{
    /**
     * @param  array<string, int|float|string>  $parameters
     */
    public function __construct(private array $parameters) {}

    /**
     * @return array{msc: Money, employee: Money, employer: Money, ec: Money}
     */
    public function monthly(Money $compensation): array
    {
        $min = Money::ofPesos($this->parameters['msc_min']);
        $max = Money::ofPesos($this->parameters['msc_max']);
        $step = Money::ofPesos($this->parameters['msc_step'])->centavos;

        // Brackets are centred on the salary credit: 5,250.00–5,749.99 => 5,500.
        $msc = Money::ofCentavos(intdiv($compensation->centavos + intdiv($step, 2), $step) * $step)->max($min)->min($max);

        $ec = $msc->isLessThan(Money::ofPesos($this->parameters['ec_threshold']))
            ? Money::ofPesos($this->parameters['ec_low'])
            : Money::ofPesos($this->parameters['ec_high']);

        return [
            'msc' => $msc,
            'employee' => $msc->multipliedBy($this->parameters['ee_rate']),
            'employer' => $msc->multipliedBy($this->parameters['er_rate']),
            'ec' => $ec,
        ];
    }
}
