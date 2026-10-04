<?php

namespace App\Features\Employees\Enums;

use App\Shared\Concerns\HasOptions;

enum Gender: string
{
    use HasOptions;

    case Male = 'male';
    case Female = 'female';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
