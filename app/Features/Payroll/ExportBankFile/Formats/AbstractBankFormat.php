<?php

namespace App\Features\Payroll\ExportBankFile\Formats;

use App\Features\Payroll\EFiling\Formats\Csv;
use App\Features\Payroll\ExportBankFile\BankFileFormat;
use App\Features\Payroll\Models\Payslip;
use App\Shared\Money\Money;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

abstract class AbstractBankFormat implements BankFileFormat
{
    public function extension(): string
    {
        return 'csv';
    }

    /**
     * @param  Collection<int, Payslip>  $credits
     */
    protected function total(Collection $credits): Money
    {
        return $credits->reduce(fn (Money $c, Payslip $p) => $c->plus($p->net_pay), Money::zero());
    }

    protected function accountName(Payslip $payslip): string
    {
        return Str::upper(Str::ascii($payslip->employee->bank_account_name ?: $payslip->employee_name));
    }

    /**
     * @param  list<list<string|float|int|null>>  $rows
     */
    protected function csv(array $rows): string
    {
        return Csv::build($rows);
    }
}
