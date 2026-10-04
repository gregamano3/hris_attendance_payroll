<?php

namespace App\Features\Payroll\EFiling\Formats;

use App\Features\Payroll\EFiling\EFilingFormat;
use App\Shared\FixedWidth;
use App\Shared\Money\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * SSS R3 contribution collection list, fixed width:
 *   00 | employer SSS no (10) | employer name (40) | applicable month YYYYMM
 *   20 | SSS no (10) | last name (20) | first name (20) | MI (1) | SS EE+ER (9, centavos) | EC (7, centavos)
 *   99 | record count (6) | total SS (12) | total EC (10)
 */
class SssR3 implements EFilingFormat
{
    public function key(): string
    {
        return 'sss-r3';
    }

    public function label(): string
    {
        return 'SSS R3 (fixed width)';
    }

    public function frequency(): string
    {
        return 'monthly';
    }

    public function render(Collection $rows, Carbon $period): string
    {
        $employer = config('hris.employer');
        $lines = ['00'.FixedWidth::digits($employer['sss_no'], 10).FixedWidth::text($employer['name'], 40).$period->format('Ym')];
        $totalSs = Money::zero();
        $totalEc = Money::zero();

        foreach ($rows as $row) {
            $ss = $row['amounts']['SSS']->plus($row['amounts']['SSS_ER']);
            $ec = $row['amounts']['SSS_EC'];
            $totalSs = $totalSs->plus($ss);
            $totalEc = $totalEc->plus($ec);
            $e = $row['employee'];

            $lines[] = '20'.FixedWidth::digits($e->sss_no, 10).FixedWidth::text($e->last_name, 20).FixedWidth::text($e->first_name, 20)
                .FixedWidth::text(mb_substr((string) $e->middle_name, 0, 1), 1).FixedWidth::amount($ss, 9).FixedWidth::amount($ec, 7);
        }

        $lines[] = '99'.str_pad((string) $rows->count(), 6, '0', STR_PAD_LEFT).FixedWidth::amount($totalSs, 12).FixedWidth::amount($totalEc, 10);

        return implode("\r\n", $lines)."\r\n";
    }

    public function filename(Carbon $period): string
    {
        return 'R3-'.$period->format('Ym').'.txt';
    }
}
