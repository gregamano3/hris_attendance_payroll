<?php

namespace App\Features\Payroll\Compute;

use App\Shared\Money\Money;

final readonly class PayslipResult
{
    /**
     * @param  list<PayslipLine>  $lines
     * @param  array<string, int|float>  $attendance
     * @param  list<string>  $warnings
     */
    public function __construct(
        public Money $dailyRate,
        public Money $hourlyRate,
        public array $lines,
        public Money $grossPay,
        public Money $taxableIncome,
        public Money $totalDeductions,
        public Money $netPay,
        public Money $employerContributions,
        public array $attendance,
        public array $warnings,
    ) {}

    /**
     * @return list<PayslipLine>
     */
    public function linesOf(string $kind): array
    {
        return array_values(array_filter($this->lines, fn (PayslipLine $line) => $line->kind === $kind));
    }

    public function amountOf(string $code): Money
    {
        return array_reduce(
            array_filter($this->lines, fn (PayslipLine $line) => $line->code === $code),
            fn (Money $carry, PayslipLine $line) => $carry->plus($line->amount),
            Money::zero(),
        );
    }
}
