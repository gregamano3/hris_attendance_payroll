<?php

namespace App\Features\Attendance\Enums;

enum TimeLogSource: string
{
    case Web = 'web';
    case Manual = 'manual';
    case Import = 'import';
    case Device = 'device';
    case Api = 'api';

    public function label(): string
    {
        return $this === self::Api ? 'API' : ucfirst($this->value);
    }
}
