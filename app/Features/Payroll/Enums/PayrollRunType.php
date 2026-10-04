<?php

namespace App\Features\Payroll\Enums;

use App\Shared\Concerns\HasOptions;

enum PayrollRunType: string
{
    use HasOptions;

    case Regular = 'regular';
    case ThirteenthMonth = 'thirteenth_month';

    public function label(): string
    {
        return match ($this) {
            self::Regular => 'Regular payroll',
            self::ThirteenthMonth => '13th month pay',
        };
    }
}
