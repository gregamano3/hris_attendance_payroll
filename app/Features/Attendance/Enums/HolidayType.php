<?php

namespace App\Features\Attendance\Enums;

use App\Shared\Concerns\HasOptions;

enum HolidayType: string
{
    use HasOptions;

    case Regular = 'regular';
    case SpecialNonWorking = 'special_non_working';

    public function label(): string
    {
        return match ($this) {
            self::Regular => 'Regular holiday',
            self::SpecialNonWorking => 'Special non-working day',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Regular => 'danger',
            self::SpecialNonWorking => 'warning',
        };
    }
}
