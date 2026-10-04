<?php

namespace App\Features\Payroll\Enums;

use App\Shared\Concerns\HasOptions;

enum TaxTreatment: string
{
    use HasOptions;

    case Taxable = 'taxable';
    case DeMinimis = 'de_minimis';
    case NonTaxable = 'non_taxable';

    public function label(): string
    {
        return match ($this) {
            self::Taxable => 'Taxable',
            self::DeMinimis => 'De minimis (non-taxable within BIR limits)',
            self::NonTaxable => 'Non-taxable',
        };
    }

    public function isTaxable(): bool
    {
        return $this === self::Taxable;
    }
}
