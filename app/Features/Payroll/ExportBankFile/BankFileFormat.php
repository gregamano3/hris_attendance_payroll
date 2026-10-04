<?php

namespace App\Features\Payroll\ExportBankFile;

use App\Features\Payroll\Models\PayrollRun;
use App\Features\Payroll\Models\Payslip;
use Illuminate\Support\Collection;

interface BankFileFormat
{
    public function key(): string;

    public function label(): string;

    public function extension(): string;

    /**
     * @param  Collection<int, Payslip>  $credits  bank-paid payslips with a positive net pay
     */
    public function render(PayrollRun $run, Collection $credits): string;
}
