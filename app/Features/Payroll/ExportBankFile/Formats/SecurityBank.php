<?php

namespace App\Features\Payroll\ExportBankFile\Formats;

use App\Features\Payroll\Models\PayrollRun;
use Illuminate\Support\Collection;

/**
 * Security Bank payroll upload (CSV with header and control total row).
 */
class SecurityBank extends AbstractBankFormat
{
    public function key(): string
    {
        return 'securitybank';
    }

    public function label(): string
    {
        return 'Security Bank';
    }

    public function render(PayrollRun $run, Collection $credits): string
    {
        $rows = [['ACCOUNT_NO', 'ACCOUNT_NAME', 'AMOUNT', 'REMARKS']];

        foreach ($credits as $p) {
            $rows[] = [$p->employee->bank_account_no, $this->accountName($p), $p->net_pay->toDecimal(), 'SALARY '.$run->pay_date->format('Ymd')];
        }

        $rows[] = ['TOTAL', (string) $credits->count(), $this->total($credits)->toDecimal(), ''];

        return $this->csv($rows);
    }
}
