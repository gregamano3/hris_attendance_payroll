<?php

namespace App\Features\Payroll\Compute;

use App\Shared\Money\Money;

final readonly class FinalPayResult
{
    /**
     * @param  list<PayslipLine>  $lines
     * @param  array<string, string|float>  $details
     */
    public function __construct(
        public array $lines,
        public Money $totalEarnings,
        public Money $totalDeductions,
        public Money $netPay,
        public array $details,
    ) {}

    public function amountOf(string $code): Money
    {
        return array_reduce($this->lines, fn (Money $c, PayslipLine $l) => $l->code === $code ? $c->plus($l->amount) : $c, Money::zero());
    }
}
