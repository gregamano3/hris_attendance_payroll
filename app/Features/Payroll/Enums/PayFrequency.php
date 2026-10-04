<?php

namespace App\Features\Payroll\Enums;

use App\Shared\Concerns\HasOptions;

enum PayFrequency: string
{
    use HasOptions;

    case SemiMonthly = 'semi_monthly';
    case Monthly = 'monthly';
    case Weekly = 'weekly';

    public function label(): string
    {
        return match ($this) {
            self::SemiMonthly => 'Semi-monthly',
            self::Monthly => 'Monthly',
            self::Weekly => 'Weekly',
        };
    }

    /**
     * Share of the monthly salary and contributions paid per run.
     */
    public function monthlyShare(): float
    {
        return match ($this) {
            self::SemiMonthly => 0.5,
            self::Monthly => 1.0,
            self::Weekly => 12 / 52,
        };
    }

    /**
     * BIR withholding table used for runs of this frequency.
     */
    public function taxTable(): string
    {
        return $this->value;
    }
}
