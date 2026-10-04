<?php

namespace App\Features\Privacy\Enums;

use App\Shared\Concerns\HasOptions;

enum RequestType: string
{
    use HasOptions;

    case Access = 'access';
    case Correction = 'correction';
    case Erasure = 'erasure';
    case Objection = 'objection';

    public function label(): string
    {
        return match ($this) {
            self::Access => 'Access to my personal data',
            self::Correction => 'Correction of my personal data',
            self::Erasure => 'Erasure or blocking',
            self::Objection => 'Objection to processing',
        };
    }
}
