<?php

namespace App\Features\Payroll\Compute;

use App\Shared\Money\Money;

/**
 * BIR graduated withholding tax: base tax + rate × excess over the lower
 * bound of the bracket the taxable income falls in.
 */
final readonly class WithholdingTaxCalculator
{
    /**
     * @param  list<array{lower: Money, upper: Money|null, base: Money, rate: float}>  $brackets
     */
    public function __construct(private array $brackets) {}

    public function compute(Money $taxableIncome): Money
    {
        if (! $taxableIncome->isGreaterThan(Money::zero())) {
            return Money::zero();
        }

        foreach ($this->brackets as $bracket) {
            $withinUpper = $bracket['upper'] === null || ! $taxableIncome->isGreaterThan($bracket['upper']);

            if (! $taxableIncome->isLessThan($bracket['lower']) && $withinUpper) {
                $excess = $taxableIncome->minus($bracket['lower']);

                return $bracket['base']->plus($excess->multipliedBy($bracket['rate']));
            }
        }

        return Money::zero();
    }
}
