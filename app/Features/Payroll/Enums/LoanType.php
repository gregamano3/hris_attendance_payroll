<?php

namespace App\Features\Payroll\Enums;

use App\Shared\Concerns\HasOptions;

enum LoanType: string
{
    use HasOptions;

    case SssSalary = 'sss_salary';
    case SssCalamity = 'sss_calamity';
    case PagIbigMultiPurpose = 'pagibig_mpl';
    case PagIbigCalamity = 'pagibig_calamity';
    case Company = 'company';
    case CashAdvance = 'cash_advance';

    public function label(): string
    {
        return match ($this) {
            self::SssSalary => 'SSS salary loan',
            self::SssCalamity => 'SSS calamity loan',
            self::PagIbigMultiPurpose => 'Pag-IBIG multi-purpose loan',
            self::PagIbigCalamity => 'Pag-IBIG calamity loan',
            self::Company => 'Company loan',
            self::CashAdvance => 'Cash advance',
        };
    }
}
