<?php

namespace App\Features\Payroll\ExportRegister;

use App\Features\Payroll\Models\PayrollRun;
use App\Features\Payroll\Models\Payslip;
use App\Shared\Xlsx;
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
        [$header, $rows] = $this->table($run);

        return response()->streamDownload(function () use ($header, $rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $header);

            foreach ($rows as $row) {
                fputcsv($out, $row);
            }

            fclose($out);
        }, sprintf('payroll-register-%s.csv', $run->period_end->format('Ymd')), ['Content-Type' => 'text/csv']);
    }

    public function xlsx(PayrollRun $run): StreamedResponse
    {
        [$header, $rows] = $this->table($run);

        // Amounts as numbers so they can be summed in Excel.
        $rows = array_map(fn (array $row) => array_map(fn ($v) => is_string($v) && is_numeric($v) && str_contains($v, '.') ? (float) $v : $v, $row), $rows);

        return Xlsx::download(sprintf('payroll-register-%s.xlsx', $run->period_end->format('Ymd')), $header, $rows, 'Register');
    }

    /**
     * @return array{0: list<string>, 1: list<list<string|null>>}
     */
    private function table(PayrollRun $run): array
    {
        $payslips = $run->payslips()->with('lines')->orderBy('employee_name')->get();
        $header = ['Employee no.', 'Name', 'Department', 'Branch', 'Cost center', ...array_values(self::COLUMNS), 'Gross', 'Deductions', 'Net pay', 'Employer share'];

        $rows = $payslips->map(fn (Payslip $payslip) => [
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
        ])->values()->all();

        return [$header, $rows];
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
