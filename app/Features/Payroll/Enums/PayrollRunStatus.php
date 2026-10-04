<?php

namespace App\Features\Payroll\Enums;

enum PayrollRunStatus: string
{
    case Draft = 'draft';
    case Computed = 'computed';
    case Finalized = 'finalized';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function badge(): string
    {
        return match ($this) {
            self::Draft => 'secondary',
            self::Computed => 'info',
            self::Finalized => 'success',
        };
    }

    public function isLocked(): bool
    {
        return $this === self::Finalized;
    }
}
