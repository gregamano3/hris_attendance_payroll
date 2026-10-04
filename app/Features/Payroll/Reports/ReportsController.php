<?php

namespace App\Features\Payroll\Reports;

use App\Features\Employees\Enums\GovernmentId;
use App\Features\Employees\Models\Employee;
use App\Features\Employees\Queries\EmployeeDirectory;
use App\Features\Payroll\Queries\AnnualCompensation;
use App\Features\Payroll\Queries\MonthlyContributions;
use App\Shared\Money\Money;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Government remittance reports (SSS R3, PhilHealth RF-1, Pag-IBIG MCRF) and
 * BIR reports (1601-C, 2316, alphalist) built from finalized payslips.
 * The CSV files follow the column order of the official forms so they can
 * be transcribed or converted for the agencies' upload tools.
 */
class ReportsController
{
    public function __construct(
        private MonthlyContributions $contributions,
        private AnnualCompensation $annual,
    ) {}

    public function index(Request $request): View
    {
        $month = $this->month($request);
        $year = $request->integer('year') ?: today()->subMonth()->year;
        $rows = $this->contributions->forMonth($month);

        return view('payroll::reports.index', [
            'month' => $month,
            'year' => $year,
            'rows' => $rows,
            'totals' => $this->totals($rows),
            'annual' => $this->annual->forYear($year),
        ]);
    }

    public function contributions(Request $request, string $agency): StreamedResponse
    {
        $month = $this->month($request);
        $rows = $this->contributions->forMonth($month);
        $name = fn (Employee $e) => [$e->last_name, $e->first_name, $e->middle_name ? mb_substr($e->middle_name, 0, 1) : ''];

        [$header, $mapper] = match ($agency) {
            'sss' => [
                ['SSS No.', 'Last name', 'First name', 'MI', 'EE share', 'ER share', 'EC', 'Total'],
                fn (array $r) => [GovernmentId::Sss->format($r['employee']->sss_no), ...$name($r['employee']),
                    ...$this->decimals($r['amounts']['SSS'], $r['amounts']['SSS_ER'], $r['amounts']['SSS_EC']),
                    $r['amounts']['SSS']->plus($r['amounts']['SSS_ER'])->plus($r['amounts']['SSS_EC'])->toDecimal()],
            ],
            'philhealth' => [
                ['PhilHealth No.', 'Last name', 'First name', 'MI', 'Monthly basic salary', 'EE share', 'ER share', 'Total'],
                fn (array $r) => [GovernmentId::PhilHealth->format($r['employee']->philhealth_no), ...$name($r['employee']),
                    ...$this->decimals($r['monthly_basic'], $r['amounts']['PHILHEALTH'], $r['amounts']['PHILHEALTH_ER']),
                    $r['amounts']['PHILHEALTH']->plus($r['amounts']['PHILHEALTH_ER'])->toDecimal()],
            ],
            'pagibig' => [
                ['Pag-IBIG MID No.', 'Last name', 'First name', 'MI', 'Monthly compensation', 'EE share', 'ER share', 'Total'],
                fn (array $r) => [GovernmentId::PagIbig->format($r['employee']->pagibig_no), ...$name($r['employee']),
                    ...$this->decimals($r['monthly_basic'], $r['amounts']['PAGIBIG'], $r['amounts']['PAGIBIG_ER']),
                    $r['amounts']['PAGIBIG']->plus($r['amounts']['PAGIBIG_ER'])->toDecimal()],
            ],
            'bir-1601c' => [
                ['TIN', 'Last name', 'First name', 'MI', 'Gross compensation', 'Taxable compensation', 'Tax withheld'],
                fn (array $r) => [GovernmentId::Tin->format($r['employee']->tin), ...$name($r['employee']),
                    ...$this->decimals($r['gross'], $r['taxable'], $r['tax'])],
            ],
            default => abort(404),
        };

        return $this->csv("{$agency}-{$month->format('Y-m')}.csv", $header, $rows->map($mapper)->all());
    }

    public function alphalist(Request $request): StreamedResponse
    {
        $year = $request->integer('year') ?: today()->year;

        return $this->csv("bir-alphalist-{$year}.csv", [
            'TIN', 'Last name', 'First name', 'Middle name', 'Minimum wage earner', 'Gross compensation',
            '13th month & other benefits (exempt)', 'De minimis / non-taxable allowances', 'Minimum wage & related pay (exempt)',
            'SSS/PhilHealth/Pag-IBIG (EE)', 'Total non-taxable', 'Taxable compensation', 'Tax due', 'Tax withheld', 'Year-end adjustment',
        ], $this->annual->forYear($year)->map(fn (array $r) => [
            GovernmentId::Tin->format($r['employee']->tin), $r['employee']->last_name, $r['employee']->first_name,
            $r['employee']->middle_name, $r['is_minimum_wage_earner'] ? 'Y' : 'N',
            ...$this->decimals($r['gross'], $r['thirteenth_month_exempt'], $r['non_taxable_allowances'], $r['minimum_wage_exempt'],
                $r['contributions'], $r['non_taxable'], $r['taxable'], $r['tax_due'], $r['tax_withheld'], $r['adjustment']),
        ])->all());
    }

    /**
     * BIR Form 2316 (certificate of compensation and tax withheld) as PDF.
     * Payroll staff can download any; employees only their own.
     */
    public function certificate(Request $request, int $year, Employee $employee, EmployeeDirectory $directory): Response
    {
        $user = $request->user();
        abort_unless($user?->can('payroll.view') || $directory->forUser($user)?->is($employee), 403);

        $row = $this->annual->forYear($year, $employee->id)->first();
        abort_if($row === null, 404, 'No finalized payroll for this employee in that year.');

        return Pdf::loadView('payroll::reports.bir-2316', ['row' => $row, 'year' => $year])
            ->setPaper('a4')
            ->download("bir-2316-{$year}-{$employee->employee_no}.pdf");
    }

    private function month(Request $request): Carbon
    {
        try {
            return Carbon::createFromFormat('Y-m', $request->string('month')->toString() ?: today()->subMonth()->format('Y-m'))->startOfMonth();
        } catch (\Throwable) {
            return today()->subMonth()->startOfMonth();
        }
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array<string, Money>
     */
    private function totals(Collection $rows): array
    {
        $totals = [];

        foreach ([...MonthlyContributions::CODES, 'TAX'] as $code) {
            $totals[$code] = $rows->reduce(fn (Money $c, array $r) => $c->plus($r['amounts'][$code]), Money::zero());
        }

        $totals['gross'] = $rows->reduce(fn (Money $c, array $r) => $c->plus($r['gross']), Money::zero());
        $totals['taxable'] = $rows->reduce(fn (Money $c, array $r) => $c->plus($r['taxable']), Money::zero());

        return $totals;
    }

    /**
     * @return list<string>
     */
    private function decimals(Money ...$amounts): array
    {
        return array_map(fn (Money $m) => $m->toDecimal(), $amounts);
    }

    /**
     * @param  list<string>  $header
     * @param  array<int, array<int, string|null>>  $rows
     */
    private function csv(string $filename, array $header, array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($header, $rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $header);

            foreach ($rows as $row) {
                fputcsv($out, $row);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
