<?php

namespace App\Features\Payroll\EFiling\Formats;

use App\Features\Payroll\EFiling\EFilingFormat;
use App\Shared\Money\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * BIR Form 1604-C alphalist in the comma separated DAT layout of the BIR
 * Alphalist Data Entry module:
 *   H1604C,<employer TIN>,<branch>,<12/31/YYYY>,<RDO>
 *   D1,1604C,<employer TIN>,<branch>,<12/31/YYYY>,<seq>,<employee TIN>,"LAST","FIRST","MIDDLE",<amounts…>
 *   C1,1604C,<employer TIN>,<branch>,<12/31/YYYY>,<totals…>
 * Schedule 1 = non-minimum wage earners, schedule 2 = minimum wage earners.
 * Validate with the BIR validation module before submission.
 */
class BirAlphalist implements EFilingFormat
{
    private const AMOUNTS = ['gross', 'thirteenth_month_exempt', 'non_taxable_allowances', 'minimum_wage_exempt', 'contributions', 'non_taxable', 'taxable', 'tax_due', 'tax_withheld', 'adjustment'];

    public function key(): string
    {
        return 'bir-alphalist';
    }

    public function label(): string
    {
        return 'BIR 1604-C alphalist (DAT)';
    }

    public function frequency(): string
    {
        return 'annual';
    }

    public function render(Collection $rows, Carbon $period): string
    {
        $employer = config('hris.employer');
        $tin = substr(preg_replace('/\D/', '', (string) $employer['tin']) ?? '', 0, 9);
        $branch = str_pad(substr(preg_replace('/\D/', '', (string) $employer['tin_branch']) ?? '', 0, 4), 4, '0', STR_PAD_LEFT);
        $date = $period->copy()->endOfYear()->format('m/d/Y');
        $key = "1604C,{$tin},{$branch},{$date}";

        $lines = ["H1604C,{$tin},{$branch},{$date},{$employer['rdo']}"];
        $totals = array_fill_keys(self::AMOUNTS, Money::zero());

        foreach ($rows->values() as $i => $row) {
            $e = $row['employee'];
            $schedule = $row['is_minimum_wage_earner'] ? 'D2' : 'D1';
            $amounts = array_map(fn (string $k) => $row[$k]->toDecimal(), self::AMOUNTS);

            foreach (self::AMOUNTS as $k) {
                $totals[$k] = $totals[$k]->plus($row[$k]);
            }

            $lines[] = implode(',', [$schedule, $key, $i + 1, substr(preg_replace('/\D/', '', (string) $e->tin) ?? '', 0, 9),
                $this->quote($e->last_name), $this->quote($e->first_name), $this->quote((string) $e->middle_name), ...$amounts]);
        }

        $lines[] = implode(',', ['C1', $key, $rows->count(), ...array_map(fn (Money $m) => $m->toDecimal(), $totals)]);

        return implode("\r\n", $lines)."\r\n";
    }

    public function filename(Carbon $period): string
    {
        $tin = substr(preg_replace('/\D/', '', (string) config('hris.employer.tin')) ?? '', 0, 9) ?: '000000000';

        return "{$tin}0000{$period->format('1231Y')}1604C.DAT";
    }

    private function quote(string $value): string
    {
        return '"'.str_replace('"', '', Name::upper($value)).'"';
    }
}
