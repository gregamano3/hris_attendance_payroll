<?php

namespace App\Features\Payroll\Compute;

use App\Shared\Money\Money;

final readonly class PayslipInput
{
    /**
     * @param  list<DayData>  $days
     * @param  list<array{kind: string, label: string, amount: Money, taxable: bool, code?: string}>  $adjustments
     * @param  list<array{from: string|null, rate: Money}>  $rateSegments  rates in effect during the period (sorted); empty = $basicRate throughout
     */
    public function __construct(
        public bool $monthlyRated,
        public Money $basicRate,
        public array $days,
        public array $adjustments = [],
        public bool $minimumWageEarner = false,
        public array $rateSegments = [],
        public ?string $periodFrom = null,
        public ?string $periodTo = null,
    ) {}
}
