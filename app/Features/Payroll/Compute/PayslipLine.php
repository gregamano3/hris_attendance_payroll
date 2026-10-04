<?php

namespace App\Features\Payroll\Compute;

use App\Shared\Money\Money;

final readonly class PayslipLine
{
    public const EARNING = 'earning';

    public const DEDUCTION = 'deduction';

    public const EMPLOYER = 'employer';

    public function __construct(
        public string $kind,
        public string $code,
        public string $label,
        public Money $amount,
        public ?float $quantity = null,
        public ?string $unit = null,
        public bool $taxable = false,
    ) {}
}
