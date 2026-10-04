<?php

namespace App\Features\Employees\Enums;

use App\Shared\Concerns\HasOptions;

enum CivilStatus: string
{
    use HasOptions;

    case Single = 'single';
    case Married = 'married';
    case Widowed = 'widowed';
    case Separated = 'separated';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
