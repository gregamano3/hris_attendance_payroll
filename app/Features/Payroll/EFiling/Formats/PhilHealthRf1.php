<?php

namespace App\Features\Payroll\EFiling\Formats;

use App\Features\Payroll\EFiling\EFilingFormat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * PhilHealth RF-1 remittance report as an EPRS-style CSV upload.
 */
class PhilHealthRf1 implements EFilingFormat
{
    public function key(): string
    {
        return 'philhealth-rf1';
    }

    public function label(): string
    {
        return 'PhilHealth RF-1 (EPRS CSV)';
    }

    public function frequency(): string
    {
        return 'monthly';
    }

    public function render(Collection $rows, Carbon $period): string
    {
        $lines = [['PHILHEALTH_NO', 'SURNAME', 'GIVEN_NAME', 'MIDDLE_NAME', 'DATE_OF_BIRTH', 'SEX', 'MONTHLY_SALARY', 'PERSONAL_SHARE', 'EMPLOYER_SHARE', 'EMPLOYEE_STATUS', 'APPLICABLE_PERIOD']];

        foreach ($rows as $row) {
            $e = $row['employee'];
            $lines[] = [
                $e->philhealth_no, Name::upper($e->last_name), Name::upper($e->first_name), Name::upper((string) $e->middle_name),
                $e->birth_date?->format('m/d/Y'), strtoupper(substr((string) $e->gender?->value, 0, 1)),
                $row['monthly_basic']->toDecimal(), $row['amounts']['PHILHEALTH']->toDecimal(), $row['amounts']['PHILHEALTH_ER']->toDecimal(),
                $e->status->isSeparated() ? 'SE' : 'A', $period->format('mY'),
            ];
        }

        return Csv::build($lines);
    }

    public function filename(Carbon $period): string
    {
        return 'RF1-'.$period->format('Ym').'.csv';
    }
}
