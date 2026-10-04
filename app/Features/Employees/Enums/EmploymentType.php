<?php

namespace App\Features\Employees\Enums;

use App\Shared\Concerns\HasOptions;

enum EmploymentType: string
{
    use HasOptions;

    case Regular = 'regular';
    case Probationary = 'probationary';
    case Contractual = 'contractual';
    case ProjectBased = 'project';

    public function label(): string
    {
        return match ($this) {
            self::Regular => 'Regular',
            self::Probationary => 'Probationary',
            self::Contractual => 'Contractual',
            self::ProjectBased => 'Project-based',
        };
    }
}
