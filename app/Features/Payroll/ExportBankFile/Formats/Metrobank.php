<?php

namespace App\Features\Payroll\ExportBankFile\Formats;

use App\Features\Payroll\Models\PayrollRun;
use Illuminate\Support\Collection;

/**
 * Metrobank payroll upload (CSV with header).
 */
class Metrobank extends AbstractBankFormat
{
    public function key(): string
    {
        return 'metrobank';
    }

    public function label(): string
    {
        return 'Metrobank';
    }

    public function render(PayrollRun $run, Collection $credits): string
    {
        $rows = [['ACCOUNT NUMBER', 'EMPLOYEE NAME', 'AMOUNT', 'REMARKS']];

        foreach ($credits as $p) {
            $rows[] = [$p->employee->bank_account_no, $this->accountName($p), $p->net_pay->toDecimal(), 'PAYROLL '.$run->pay_date->format('m/d/Y')];
        }

        return $this->csv($rows);
    }
}
