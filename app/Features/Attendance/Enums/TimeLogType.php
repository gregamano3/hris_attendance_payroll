<?php

namespace App\Features\Attendance\Enums;

use App\Shared\Concerns\HasOptions;

enum TimeLogType: string
{
    use HasOptions;

    case In = 'in';
    case Out = 'out';
    case BreakOut = 'break_out';
    case BreakIn = 'break_in';

    public function label(): string
    {
        return match ($this) {
            self::In => 'Time in',
            self::Out => 'Time out',
            self::BreakOut => 'Break start',
            self::BreakIn => 'Break end',
        };
    }
}
