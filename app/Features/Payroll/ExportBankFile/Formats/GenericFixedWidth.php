<?php

namespace App\Features\Payroll\ExportBankFile\Formats;

use App\Features\Payroll\Models\PayrollRun;
use App\Shared\FixedWidth;
use Illuminate\Support\Collection;

/**
 * H | company (30) | pay date | count (6) | total (15)
 * D | account (20) | amount (15) | name (40) | employee no (15)
 * T | count (6) | total (15)
 */
class GenericFixedWidth extends AbstractBankFormat
{
    public function key(): string
    {
        return 'txt';
    }

    public function label(): string
    {
        return 'Generic fixed width';
    }

    public function extension(): string
    {
        return 'txt';
    }

    public function render(PayrollRun $run, Collection $credits): string
    {
        $total = $this->total($credits);
        $count = str_pad((string) $credits->count(), 6, '0', STR_PAD_LEFT);
        $lines = ['H'.FixedWidth::text(config('app.name'), 30).$run->pay_date->format('Ymd').$count.FixedWidth::amount($total, 15)];

        foreach ($credits as $p) {
            $lines[] = 'D'.str_pad((string) $p->employee->bank_account_no, 20).FixedWidth::amount($p->net_pay, 15)
                .FixedWidth::text($this->accountName($p), 40).str_pad(mb_substr($p->employee_no, 0, 15), 15);
        }

        $lines[] = 'T'.$count.FixedWidth::amount($total, 15);

        return implode("\r\n", $lines)."\r\n";
    }
}
