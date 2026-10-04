<?php

namespace App\Features\Payroll\ExportBankFile\Formats;

use App\Features\Payroll\Models\PayrollRun;
use App\Shared\FixedWidth;
use Illuminate\Support\Collection;

/**
 * BPI payroll credit (fixed width):
 *   H | company code (5) | funding account (10) | credit date MMDDYY | count (5) | total (15)
 *   D | account no (10) | amount (12) | name (40)
 */
class Bpi extends AbstractBankFormat
{
    public function key(): string
    {
        return 'bpi';
    }

    public function label(): string
    {
        return 'BPI';
    }

    public function extension(): string
    {
        return 'txt';
    }

    public function render(PayrollRun $run, Collection $credits): string
    {
        $employer = config('hris.employer');
        $lines = ['H'.FixedWidth::text($employer['bank_company_code'], 5).FixedWidth::digits($employer['bank_account_no'], 10)
            .$run->pay_date->format('mdy').str_pad((string) $credits->count(), 5, '0', STR_PAD_LEFT).FixedWidth::amount($this->total($credits), 15)];

        foreach ($credits as $p) {
            $lines[] = 'D'.FixedWidth::digits($p->employee->bank_account_no, 10).FixedWidth::amount($p->net_pay, 12).FixedWidth::text($this->accountName($p), 40);
        }

        return implode("\r\n", $lines)."\r\n";
    }
}
