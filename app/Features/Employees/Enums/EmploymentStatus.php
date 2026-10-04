<?php

namespace App\Features\Employees\Enums;

use App\Shared\Concerns\HasOptions;

enum EmploymentStatus: string
{
    use HasOptions;

    case Active = 'active';
    case OnLeave = 'on_leave';
    case Resigned = 'resigned';
    case Terminated = 'terminated';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::OnLeave => 'On leave',
            self::Resigned => 'Resigned',
            self::Terminated => 'Terminated',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::OnLeave => 'warning',
            self::Resigned, self::Terminated => 'secondary',
        };
    }

    public function isSeparated(): bool
    {
        return in_array($this, [self::Resigned, self::Terminated], true);
    }
}
