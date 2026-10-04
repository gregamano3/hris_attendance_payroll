<?php

namespace App\Features\Payroll\ExportBankFile;

use App\Features\Payroll\Models\PayrollRun;
use App\Features\Payroll\Models\Payslip;
use App\Shared\Money\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

/**
 * Payroll credit file for the bank, for finalized runs only. Employees
 * without bank details (or with nothing to credit) are left out and listed
 * on the run page for cash/check payment.
 *
 * Formats live in ExportBankFile\Formats (generic CSV / fixed width and
 * bank templates). Validate a new template with the bank before go-live.
 */
class ExportBankFileController
{
    public const FORMATS = [
        Formats\GenericCsv::class, Formats\GenericFixedWidth::class, Formats\Bdo::class,
        Formats\Bpi::class, Formats\Metrobank::class, Formats\SecurityBank::class,
    ];

    public function __invoke(PayrollRun $run, string $format): Response|RedirectResponse
    {
        if (! $run->isLocked()) {
            return back()->with('error', 'Finalize the payroll run before generating the bank file.');
        }

        $class = collect(self::FORMATS)->first(fn (string $c) => (new $c)->key() === $format) ?? abort(404);
        /** @var BankFileFormat $file */
        $file = new $class;

        return response($file->render($run, self::credits($run)), 200, [
            'Content-Type' => $file->extension() === 'txt' ? 'text/plain' : 'text/csv',
            'Content-Disposition' => sprintf('attachment; filename="bank-credit-%s-%s.%s"', $file->key(), $run->pay_date->format('Ymd'), $file->extension()),
        ]);
    }

    /**
     * @return list<BankFileFormat>
     */
    public static function formats(): array
    {
        return array_map(fn (string $c) => new $c, self::FORMATS);
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
