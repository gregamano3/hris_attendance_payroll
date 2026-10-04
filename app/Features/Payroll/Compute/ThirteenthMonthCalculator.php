<?php

namespace App\Features\Payroll\Compute;

use App\Shared\Money\Money;

/**
 * PD 851: 13th month pay = total basic salary earned during the calendar
 * year ÷ 12. Tax-exempt up to the yearly ceiling; the excess is taxable.
 */
final readonly class ThirteenthMonthCalculator
{
    public function __construct(private Money $exemptCeiling) {}

    /**
     * @param  iterable<Money>  $basicEarnings  basic salary of each payslip of the year
     * @param  Money|null  $alreadyExempted  exemption already used this year (e.g. 13th month paid in final pay)
     * @return array{basic: Money, amount: Money, exempt: Money, taxable: Money}
     */
    public function compute(iterable $basicEarnings, ?Money $alreadyExempted = null): array
    {
        $basic = Money::zero();

        foreach ($basicEarnings as $earning) {
            $basic = $basic->plus($earning);
        }

        $amount = $basic->max(Money::zero())->dividedBy(12);
        $remainingExemption = $this->exemptCeiling->minus($alreadyExempted ?? Money::zero())->max(Money::zero());
        $exempt = $amount->min($remainingExemption);

        return [
            'basic' => $basic,
            'amount' => $amount,
            'exempt' => $exempt,
            'taxable' => $amount->minus($exempt),
        ];
    }
}
