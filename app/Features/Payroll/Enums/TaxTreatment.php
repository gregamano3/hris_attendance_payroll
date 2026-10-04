<?php

namespace App\Features\Payroll\Enums;

use App\Shared\Concerns\HasOptions;

enum TaxTreatment: string
{
    use HasOptions;

    case Taxable = 'taxable';
    case DeMinimis = 'de_minimis';
    case NonTaxable = 'non_taxable';
    case Hazard = 'hazard';

    public function label(): string
    {
        return match ($this) {
            self::Taxable => 'Taxable',
            self::DeMinimis => 'De minimis (non-taxable within BIR limits)',
            self::NonTaxable => 'Non-taxable',
            self::Hazard => 'Hazard pay (taxable; exempt for minimum wage earners)',
        };
    }

    public function isTaxable(): bool
    {
        return in_array($this, [self::Taxable, self::Hazard], true);
    }
}
