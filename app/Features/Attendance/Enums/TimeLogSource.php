<?php

namespace App\Features\Attendance\Enums;

enum TimeLogSource: string
{
    case Web = 'web';
    case Manual = 'manual';
    case Import = 'import';
    case Device = 'device';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
