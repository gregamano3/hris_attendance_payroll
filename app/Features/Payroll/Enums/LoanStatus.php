<?php

namespace App\Features\Payroll\Enums;

enum LoanStatus: string
{
    case Active = 'active';
    case Paid = 'paid';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function badge(): string
    {
        return match ($this) {
            self::Active => 'primary',
            self::Paid => 'success',
            self::Cancelled => 'secondary',
        };
    }
}
