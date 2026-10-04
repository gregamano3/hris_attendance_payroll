<?php

namespace App\Features\Payroll\Queries;

use App\Features\Employees\Models\Employee;
use App\Features\Payroll\Compute\WithholdingTaxCalculator;
use App\Features\Payroll\Enums\PayrollRunStatus;
use App\Features\Payroll\Models\Payslip;
use App\Features\Payroll\Models\PayslipLine;
use App\Shared\Money\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Year-end figures per employee (BIR Form 2316 / alphalist) from every
 * finalized payslip of the year, with the annualized income tax.
 */
class AnnualCompensation
{
    public function __construct(private StatutoryRates $rates) {}

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function forYear(int $year, ?int $employeeId = null): Collection
    {
        $payslips = Payslip::query()
            ->with(['lines', 'employee'])
            ->when($employeeId, fn ($q, $id) => $q->where('employee_id', $id))
            ->whereHas('run', fn ($q) => $q->where('status', PayrollRunStatus::Finalized)->whereYear('period_end', $year))
            ->get();

        $tax = new WithholdingTaxCalculator($this->rates->taxBrackets('annual', Carbon::create($year, 12, 31)));

        return $payslips->groupBy('employee_id')
            ->map(fn (Collection $slips) => $this->summarize($slips, $tax))
            ->sortBy(fn (array $row) => $row['employee']->last_name.' '.$row['employee']->first_name)
            ->values();
    }

    /**
     * @param  Collection<int, Payslip>  $slips
     * @return array<string, mixed>
     */
    private function summarize(Collection $slips, WithholdingTaxCalculator $tax): array
    {
        $lines = $slips->flatMap(fn (Payslip $p) => $p->lines->map(fn (PayslipLine $l) => [$l, $p->is_minimum_wage_earner]));
        $sum = fn (callable $filter) => $lines->filter(fn (array $pair) => $filter(...$pair))
            ->reduce(fn (Money $carry, array $pair) => $carry->plus($pair[0]->amount), Money::zero());

        $isEarning = fn (PayslipLine $l) => $l->kind === 'earning';

        $gross = $sum(fn (PayslipLine $l) => $isEarning($l));
        $thirteenthExempt = $sum(fn (PayslipLine $l) => $l->code === 'THIRTEENTH_MONTH');
        $nonTaxableAllowances = $sum(fn (PayslipLine $l) => $isEarning($l) && $l->code === 'ALLOWANCE' && ! $l->taxable);
        $mweExempt = $sum(fn (PayslipLine $l, bool $mwe) => $mwe && $isEarning($l) && ! $l->taxable
            && ! in_array($l->code, ['ALLOWANCE', 'THIRTEENTH_MONTH'], true));
        $contributions = $sum(fn (PayslipLine $l) => in_array($l->code, ['SSS', 'PHILHEALTH', 'PAGIBIG'], true));
        $withheld = $sum(fn (PayslipLine $l) => $l->code === 'TAX');

        $taxable = $slips->reduce(fn (Money $carry, Payslip $p) => $carry->plus($p->taxable_income), Money::zero());
        $taxDue = $tax->compute($taxable);

        return [
            'employee' => $slips->first()?->employee,
            'is_minimum_wage_earner' => (bool) $slips->last()?->is_minimum_wage_earner,
            'gross' => $gross,
            'thirteenth_month_exempt' => $thirteenthExempt,
            'non_taxable_allowances' => $nonTaxableAllowances,
            'minimum_wage_exempt' => $mweExempt,
            'contributions' => $contributions,
            'non_taxable' => $gross->minus($taxable)->max(Money::zero()),
            'taxable' => $taxable,
            'tax_due' => $taxDue,
            'tax_withheld' => $withheld,
            'adjustment' => $taxDue->minus($withheld), // positive: still to withhold, negative: refund
        ];
    }
}
