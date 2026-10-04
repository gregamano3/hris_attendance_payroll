<?php

namespace App\Features\Payroll\Compute;

use App\Shared\Money\Money;

final readonly class FinalPayInput
{
    /**
     * @param  list<Money>  $basicEarningsThisYear  basic salary of each finalized payslip of the year
     * @param  array<string, Money>  $loanBalances  label => outstanding balance
     * @param  list<array{kind: string, label: string, amount: Money, taxable: bool}>  $adjustments
     */
    public function __construct(
        public Money $dailyRate,
        public array $basicEarningsThisYear,
        public Money $thirteenthMonthPaid,
        public Money $exemptionUsed,
        public float $unusedLeaveDays,
        public array $loanBalances,
        public array $adjustments,
        public Money $taxableToDate,
        public Money $taxWithheldToDate,
        public bool $minimumWageEarner = false,
    ) {}
}
