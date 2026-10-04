<?php

namespace App\Features\Payroll\Compute;

use App\Features\Attendance\Queries\LeaveBalances;
use App\Features\Employees\Enums\RateType;
use App\Features\Payroll\Enums\LoanStatus;
use App\Features\Payroll\Enums\PayrollRunStatus;
use App\Features\Payroll\Enums\PayrollRunType;
use App\Features\Payroll\Models\FinalPay;
use App\Features\Payroll\Models\FinalPayAdjustment;
use App\Features\Payroll\Models\Loan;
use App\Features\Payroll\Models\PayslipLine as PayslipLineModel;
use App\Features\Payroll\Queries\AnnualCompensation;
use App\Features\Payroll\Queries\BasicEarnings;
use App\Features\Payroll\Queries\StatutoryRates;
use App\Shared\Money\Money;

/**
 * Gathers the year-to-date payroll data of a separated employee and stores
 * the final pay computed by FinalPayCalculator.
 */
class ComputeFinalPay
{
    public function __construct(
        private BasicEarnings $basicEarnings,
        private AnnualCompensation $annual,
        private StatutoryRates $rates,
        private LeaveBalances $leaves,
    ) {}

    public function handle(FinalPay $finalPay): FinalPay
    {
        abort_if($finalPay->isLocked(), 409, 'Finalized final pay cannot be recomputed.');

        $employee = $finalPay->employee;
        $year = $finalPay->separation_date->year;
        $ytd = $this->annual->forYear($year, $employee->id)->first();
        $ceiling = Money::ofPesos(config('hris.payroll.thirteenth_month_exempt_ceiling'));

        $calculator = new FinalPayCalculator(
            new ThirteenthMonthCalculator($ceiling),
            new WithholdingTaxCalculator($this->rates->taxBrackets('annual', $finalPay->separation_date)),
        );

        $daily = $employee->rate_type === RateType::Monthly
            ? $employee->basic_rate->multipliedBy(12)->dividedBy((int) config('hris.payroll.days_per_year'))
            : $employee->basic_rate;

        $loans = Loan::query()->where('employee_id', $employee->id)->where('status', LoanStatus::Active)->where('balance', '>', 0)->get();

        $result = $calculator->compute(new FinalPayInput(
            dailyRate: $daily,
            basicEarningsThisYear: array_values($this->basicEarnings->forYear($year, $employee->id)->get($employee->id, [])),
            thirteenthMonthPaid: $this->thirteenthMonthPaid($employee->id, $year),
            exemptionUsed: $ytd['thirteenth_month_exempt'] ?? Money::zero(),
            unusedLeaveDays: $this->leaves->unusedConvertibleDays($employee->id, $year),
            loanBalances: $loans->mapWithKeys(fn (Loan $l) => [$l->type->label().' #'.$l->id => $l->balance])->all(),
            adjustments: $finalPay->adjustments()->get()->map(fn (FinalPayAdjustment $a) => [
                'kind' => $a->kind, 'label' => $a->label, 'amount' => $a->amount, 'taxable' => $a->taxable,
            ])->values()->all(),
            taxableToDate: $ytd['taxable'] ?? Money::zero(),
            taxWithheldToDate: $ytd['tax_withheld'] ?? Money::zero(),
            minimumWageEarner: $employee->is_minimum_wage_earner,
        ));

        $finalPay->update([
            'status' => PayrollRunStatus::Computed,
            'computed_at' => now(),
            'total_earnings' => $result->totalEarnings,
            'total_deductions' => $result->totalDeductions,
            'net_pay' => $result->netPay,
            'details' => $result->details,
            'lines' => array_map(fn (PayslipLine $l) => [
                'kind' => $l->kind, 'code' => $l->code, 'label' => $l->label, 'quantity' => $l->quantity,
                'unit' => $l->unit, 'amount' => $l->amount->toDecimal(), 'taxable' => $l->taxable,
            ], $result->lines),
        ]);

        return $finalPay->refresh();
    }

    private function thirteenthMonthPaid(int $employeeId, int $year): Money
    {
        $centavos = PayslipLineModel::query()
            ->join('payslips', 'payslips.id', '=', 'payslip_lines.payslip_id')
            ->join('payroll_runs', 'payroll_runs.id', '=', 'payslips.payroll_run_id')
            ->where('payslips.employee_id', $employeeId)
            ->where('payroll_runs.type', PayrollRunType::ThirteenthMonth)
            ->where('payroll_runs.status', PayrollRunStatus::Finalized)
            ->whereYear('payroll_runs.period_end', $year)
            ->whereIn('payslip_lines.code', ['THIRTEENTH_MONTH', 'THIRTEENTH_MONTH_TAXABLE'])
            ->sum('payslip_lines.amount');

        return Money::ofCentavos((int) $centavos);
    }
}
