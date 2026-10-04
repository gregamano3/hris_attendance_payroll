<?php

namespace App\Features\Privacy\Enums;

enum RequestStatus: string
{
    case Open = 'open';
    case Completed = 'completed';
    case Rejected = 'rejected';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function badge(): string
    {
        return match ($this) {
            self::Open => 'warning',
            self::Completed => 'success',
            self::Rejected => 'secondary',
        };
    }
}
