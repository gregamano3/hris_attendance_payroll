<?php

namespace App\Features\Payroll\ExportBankFile;

use App\Features\Payroll\Models\PayrollRun;
use App\Features\Payroll\Models\Payslip;
use App\Shared\Money\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Payroll credit file for the bank, for finalized runs only. Employees
 * without bank details (or with nothing to credit) are left out and listed
 * on the run page for cash/check payment.
 *
 * Formats:
 *  - csv: account no., account name, amount, employee no., reference
 *  - txt: fixed width — H|company|pay date|count|total, D lines, T|count|total
 */
class ExportBankFileController
{
    public function __invoke(PayrollRun $run, string $format): StreamedResponse|RedirectResponse
    {
        if (! $run->isLocked()) {
            return back()->with('error', 'Finalize the payroll run before generating the bank file.');
        }

        $credits = self::credits($run);
        $total = $credits->reduce(fn (Money $c, Payslip $p) => $c->plus($p->net_pay), Money::zero());
        $reference = 'PAYROLL '.$run->pay_date->format('Ymd');
        $filename = sprintf('bank-credit-%s.%s', $run->pay_date->format('Ymd'), $format === 'txt' ? 'txt' : 'csv');

        return response()->streamDownload(function () use ($credits, $total, $reference, $run, $format) {
            $out = fopen('php://output', 'w');

            if ($format === 'txt') {
                fwrite($out, implode('', [
                    'H',
                    str_pad(Str::upper(Str::ascii(Str::limit((string) config('app.name'), 30, ''))), 30),
                    $run->pay_date->format('Ymd'),
                    str_pad((string) $credits->count(), 6, '0', STR_PAD_LEFT),
                    str_pad((string) $total->centavos, 15, '0', STR_PAD_LEFT),
                ])."\r\n");

                foreach ($credits as $payslip) {
                    fwrite($out, implode('', [
                        'D',
                        str_pad((string) $payslip->employee->bank_account_no, 20),
                        str_pad((string) $payslip->net_pay->centavos, 15, '0', STR_PAD_LEFT),
                        str_pad(Str::upper(Str::ascii(Str::limit($payslip->employee->bank_account_name ?: $payslip->employee_name, 40, ''))), 40),
                        str_pad(Str::limit($payslip->employee_no, 15, ''), 15),
                    ])."\r\n");
                }

                fwrite($out, 'T'.str_pad((string) $credits->count(), 6, '0', STR_PAD_LEFT).str_pad((string) $total->centavos, 15, '0', STR_PAD_LEFT)."\r\n");
            } else {
                fputcsv($out, ['Account number', 'Account name', 'Amount', 'Employee no.', 'Bank', 'Reference']);

                foreach ($credits as $payslip) {
                    fputcsv($out, [
                        $payslip->employee->bank_account_no,
                        $payslip->employee->bank_account_name ?: $payslip->employee_name,
                        $payslip->net_pay->toDecimal(),
                        $payslip->employee_no,
                        $payslip->employee->bank_name,
                        $reference,
                    ]);
                }
            }

            fclose($out);
        }, $filename, ['Content-Type' => $format === 'txt' ? 'text/plain' : 'text/csv']);
    }

    /**
     * @return Collection<int, Payslip>
     */
    public static function credits(PayrollRun $run): Collection
    {
        return $run->payslips()->with('employee')->orderBy('employee_name')->get()
            ->filter(fn (Payslip $p) => $p->employee->hasBankAccount() && $p->net_pay->isGreaterThan(Money::zero()))
            ->values();
    }

    /**
     * @return Collection<int, Payslip>
     */
    public static function withoutBank(PayrollRun $run): Collection
    {
        return $run->payslips()->with('employee')->orderBy('employee_name')->get()
            ->filter(fn (Payslip $p) => ! $p->employee->hasBankAccount() && $p->net_pay->isGreaterThan(Money::zero()))
            ->values();
    }
}
