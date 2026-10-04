<?php

namespace App\Features\Payroll\ExportBankFile\Formats;

use App\Features\Payroll\Models\PayrollRun;
use Illuminate\Support\Collection;

/**
 * BDO payroll credit upload (CSV): account number, amount, account name.
 */
class Bdo extends AbstractBankFormat
{
    public function key(): string
    {
        return 'bdo';
    }

    public function label(): string
    {
        return 'BDO';
    }

    public function render(PayrollRun $run, Collection $credits): string
    {
        return $this->csv($credits->map(fn ($p) => [
            str_pad((string) $p->employee->bank_account_no, 12, '0', STR_PAD_LEFT), $p->net_pay->toDecimal(), $this->accountName($p),
        ])->values()->all());
    }
}
