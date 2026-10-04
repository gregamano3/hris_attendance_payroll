<?php

namespace App\Features\Attendance\Enums;

enum TimeLogSource: string
{
    case Web = 'web';
    case Manual = 'manual';
    case Import = 'import';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
