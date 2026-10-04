<?php

namespace App\Features\Payroll\Queries;

use App\Features\Payroll\Enums\PayrollRunStatus;
use App\Features\Payroll\Enums\PayrollRunType;
use App\Features\Payroll\Models\PayslipLine;
use App\Shared\Money\Money;
use Illuminate\Support\Collection;

/**
 * Basic salary earned per employee in finalized regular payroll runs, the
 * base of the 13th month pay.
 */
class BasicEarnings
{
    /**
     * @return Collection<int|string, array<int, Money>> employee id => basic salary of each payslip
     */
    public function forYear(int $year, ?int $employeeId = null): Collection
    {
        return PayslipLine::query()
            ->join('payslips', 'payslips.id', '=', 'payslip_lines.payslip_id')
            ->join('payroll_runs', 'payroll_runs.id', '=', 'payslips.payroll_run_id')
            ->where('payroll_runs.type', PayrollRunType::Regular)
            ->where('payroll_runs.status', PayrollRunStatus::Finalized)
            ->whereYear('payroll_runs.period_end', $year)
            ->whereIn('payslip_lines.code', config('hris.payroll.thirteenth_month_basic_codes'))
            ->when($employeeId, fn ($q, $id) => $q->where('payslips.employee_id', $id))
            ->groupBy('payslips.id', 'payslips.employee_id')
            ->selectRaw('payslips.employee_id, sum(payslip_lines.amount) as basic')
            ->get()
            ->groupBy('employee_id')
            ->map(fn (Collection $rows) => $rows->map(fn ($row) => Money::ofCentavos((int) $row->getAttribute('basic')))->values()->all());
    }
}
