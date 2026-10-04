<?php

namespace App\Features\Payroll\Compute;

use App\Shared\Money\Money;

final readonly class PayslipInput
{
    /**
     * @param  list<DayData>  $days
     * @param  list<array{kind: string, label: string, amount: Money, taxable: bool}>  $adjustments
     */
    public function __construct(
        public bool $monthlyRated,
        public Money $basicRate,
        public array $days,
        public array $adjustments = [],
        public bool $minimumWageEarner = false,
    ) {}
}
