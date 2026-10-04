<?php

namespace App\Features\Payroll\Queries;

use App\Features\Employees\Models\Employee;
use App\Features\Payroll\Enums\PayrollRunStatus;
use App\Features\Payroll\Models\Payslip;
use App\Shared\Money\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Employee and employer contributions per employee for a month, from the
 * payslips of finalized runs whose period ends in that month.
 */
class MonthlyContributions
{
    public const CODES = ['SSS', 'SSS_ER', 'SSS_EC', 'PHILHEALTH', 'PHILHEALTH_ER', 'PAGIBIG', 'PAGIBIG_ER'];

    /**
     * @return Collection<int, array<string, mixed>> keys: employee, monthly_basic, gross, taxable, tax, amounts (code => Money)
     */
    public function forMonth(Carbon $month): Collection
    {
        $payslips = Payslip::query()
            ->with(['lines' => fn ($q) => $q->whereIn('code', [...self::CODES, 'TAX']), 'employee'])
            ->whereHas('run', fn ($q) => $q->where('status', PayrollRunStatus::Finalized)
                ->whereBetween('period_end', [$month->copy()->startOfMonth()->toDateString(), $month->copy()->endOfMonth()->toDateString()]))
            ->get();

        /** @var Collection<int, array<string, mixed>> $rows */
        $rows = $payslips->groupBy('employee_id')->map(function (Collection $slips): array {
            /** @var Payslip $first */
            $first = $slips->first();
            $amounts = [];

            foreach ([...self::CODES, 'TAX'] as $code) {
                $amounts[$code] = $this->sum($slips->flatMap(fn (Payslip $p) => $p->lines->where('code', $code)->pluck('amount')));
            }

            return [
                'employee' => $first->employee,
                'monthly_basic' => $first->rate_type === 'monthly'
                    ? $first->basic_rate
                    : $first->daily_rate->multipliedBy(config('hris.payroll.days_per_year') / 12),
                'gross' => $this->sum($slips->pluck('gross_pay')),
                'taxable' => $this->sum($slips->pluck('taxable_income')),
                'tax' => $amounts['TAX'],
                'amounts' => $amounts,
            ];
        })->sortBy(fn (array $row) => $row['employee']->last_name.' '.$row['employee']->first_name)->values()->toBase();

        return $rows;
    }

    /**
     * @param  iterable<Money>  $amounts
     */
    private function sum(iterable $amounts): Money
    {
        $total = Money::zero();

        foreach ($amounts as $amount) {
            $total = $total->plus($amount);
        }

        return $total;
    }
}
