<?php

namespace App\Features\Payroll\ExportBankFile\Formats;

use App\Features\Payroll\Models\PayrollRun;
use Illuminate\Support\Collection;

class GenericCsv extends AbstractBankFormat
{
    public function key(): string
    {
        return 'csv';
    }

    public function label(): string
    {
        return 'Generic CSV';
    }

    public function render(PayrollRun $run, Collection $credits): string
    {
        $reference = 'PAYROLL '.$run->pay_date->format('Ymd');
        $rows = [['Account number', 'Account name', 'Amount', 'Employee no.', 'Bank', 'Reference']];

        foreach ($credits as $p) {
            $rows[] = [$p->employee->bank_account_no, $p->employee->bank_account_name ?: $p->employee_name, $p->net_pay->toDecimal(), $p->employee_no, $p->employee->bank_name, $reference];
        }

        return $this->csv($rows);
    }
}
