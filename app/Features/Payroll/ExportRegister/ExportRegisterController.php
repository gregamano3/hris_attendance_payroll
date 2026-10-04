<?php

namespace App\Features\Payroll\ExportRegister;

use App\Features\Payroll\Models\PayrollRun;
use App\Features\Payroll\Models\Payslip;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Payroll register as CSV (one row per payslip) for accounting and banks.
 */
class ExportRegisterController
{
    private const COLUMNS = [
        'BASIC' => 'Basic', 'ABSENCES' => 'Absences', 'TARDINESS' => 'Late/UT', 'OVERTIME' => 'Overtime',
        'PREMIUM' => 'Premiums', 'NIGHT_DIFF' => 'Night diff', 'ALLOWANCE' => 'Allowances',
        'SSS' => 'SSS', 'PHILHEALTH' => 'PhilHealth', 'PAGIBIG' => 'Pag-IBIG', 'TAX' => 'Tax',
        'LOANS' => 'Loans', 'OTHER_DEDUCTION' => 'Other deductions',
    ];

    public function __invoke(PayrollRun $run): StreamedResponse
    {
        $payslips = $run->payslips()->with('lines')->orderBy('employee_name')->get();

        return response()->streamDownload(function () use ($payslips) {
            $out = fopen('php://output', 'w');

            fputcsv($out, ['Employee no.', 'Name', 'Department', 'Branch', 'Cost center', ...array_values(self::COLUMNS), 'Gross', 'Deductions', 'Net pay', 'Employer share']);

            foreach ($payslips as $payslip) {
                fputcsv($out, [
                    $payslip->employee_no,
                    $payslip->employee_name,
                    $payslip->department,
                    $payslip->branch,
                    $payslip->cost_center,
                    ...array_map(fn (string $code) => $this->sumByPrefix($payslip, $code), array_keys(self::COLUMNS)),
                    $payslip->gross_pay->toDecimal(),
                    $payslip->total_deductions->toDecimal(),
                    $payslip->net_pay->toDecimal(),
                    $payslip->employer_contributions->toDecimal(),
                ]);
            }

            fclose($out);
        }, sprintf('payroll-register-%s.csv', $run->period_end->format('Ymd')), ['Content-Type' => 'text/csv']);
    }

    private function sumByPrefix(Payslip $payslip, string $code): string
    {
        $prefix = match ($code) {
            'OVERTIME' => 'OT_',
            'PREMIUM' => 'PREMIUM_',
            'LOANS' => 'LOAN_',
            default => null,
        };

        $centavos = $payslip->lines
            ->filter(fn ($line) => $prefix ? str_starts_with($line->code, $prefix) : $line->code === $code)
            ->sum(fn ($line) => $line->amount->centavos);

        return number_format($centavos / 100, 2, '.', '');
    }
}
