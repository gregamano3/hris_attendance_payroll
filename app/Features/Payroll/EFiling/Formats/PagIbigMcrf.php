<?php

namespace App\Features\Payroll\EFiling\Formats;

use App\Features\Payroll\EFiling\EFilingFormat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Pag-IBIG Membership Contributions Remittance Form (MCRF) CSV upload.
 */
class PagIbigMcrf implements EFilingFormat
{
    public function key(): string
    {
        return 'pagibig-mcrf';
    }

    public function label(): string
    {
        return 'Pag-IBIG MCRF (CSV)';
    }

    public function frequency(): string
    {
        return 'monthly';
    }

    public function render(Collection $rows, Carbon $period): string
    {
        $lines = [['PAGIBIG_MID', 'LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME', 'BIRTHDATE', 'PERIOD_COVERED', 'MONTHLY_COMPENSATION', 'EE_SHARE', 'ER_SHARE', 'TIN', 'MEMBERSHIP_PROGRAM']];

        foreach ($rows as $row) {
            $e = $row['employee'];
            $lines[] = [
                $e->pagibig_no, Name::upper($e->last_name), Name::upper($e->first_name), Name::upper((string) $e->middle_name),
                $e->birth_date?->format('m/d/Y'), $period->format('Ym'), $row['monthly_basic']->toDecimal(),
                $row['amounts']['PAGIBIG']->toDecimal(), $row['amounts']['PAGIBIG_ER']->toDecimal(), $e->tin, 'F1',
            ];
        }

        return Csv::build($lines);
    }

    public function filename(Carbon $period): string
    {
        return 'MCRF-'.$period->format('Ym').'.csv';
    }
}
