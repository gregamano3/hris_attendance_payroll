<?php

namespace App\Features\Employees\Enums;

use App\Shared\Concerns\HasOptions;

enum RateType: string
{
    use HasOptions;

    case Monthly = 'monthly';
    case Daily = 'daily';

    public function label(): string
    {
        return match ($this) {
            self::Monthly => 'Monthly',
            self::Daily => 'Daily',
        };
    }

    public function unit(): string
    {
        return match ($this) {
            self::Monthly => 'month',
            self::Daily => 'day',
        };
    }
}
