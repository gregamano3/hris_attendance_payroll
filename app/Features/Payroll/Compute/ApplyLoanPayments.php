<?php

namespace App\Features\Payroll\Compute;

use App\Features\Payroll\Enums\LoanStatus;
use App\Features\Payroll\Models\Loan;
use App\Features\Payroll\Models\LoanPayment;
use App\Features\Payroll\Models\PayrollRun;
use App\Features\Payroll\Models\PayslipLine;
use App\Shared\Money\Money;

/**
 * Records the loan deductions of a run being finalized and reduces the loan
 * balances. Runs inside the finalize transaction.
 */
class ApplyLoanPayments
{
    public function handle(PayrollRun $run): void
    {
        $lines = PayslipLine::query()
            ->whereIn('payslip_id', $run->payslips()->select('id'))
            ->where('code', 'like', Loan::LINE_PREFIX.'%')
            ->get();

        foreach ($lines as $line) {
            $loan = Loan::query()->lockForUpdate()->find((int) substr($line->code, strlen(Loan::LINE_PREFIX)));

            if ($loan === null || $loan->payments()->where('payroll_run_id', $run->id)->exists()) {
                continue;
            }

            $paid = $line->amount->min($loan->balance);
            $balance = $loan->balance->minus($paid)->max(Money::zero());

            LoanPayment::query()->create([
                'loan_id' => $loan->id,
                'payroll_run_id' => $run->id,
                'amount' => $paid,
                'balance_after' => $balance,
            ]);

            $loan->update([
                'balance' => $balance,
                'status' => $balance->isZero() ? LoanStatus::Paid : $loan->status,
            ]);
        }
    }
}
