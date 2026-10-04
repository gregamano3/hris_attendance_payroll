<?php

namespace App\Features\Payroll\Compute;

use App\Features\Employees\Models\Employee;
use App\Features\Payroll\Enums\PayrollRunStatus;
use App\Features\Payroll\Models\PayrollRun;
use App\Features\Payroll\Models\Payslip;
use App\Features\Payroll\Queries\BasicEarnings;
use App\Shared\Money\Money;
use Illuminate\Support\Facades\DB;

/**
 * Generates 13th month payslips from the basic salary of the finalized
 * regular payroll runs of the run's year. No contributions are deducted.
 */
class ComputeThirteenthMonthRun
{
    public function __construct(private BasicEarnings $earnings) {}

    public function handle(PayrollRun $run): PayrollRun
    {
        abort_if($run->isLocked(), 409, 'Finalized payroll runs cannot be recomputed.');

        $calculator = new ThirteenthMonthCalculator(Money::ofPesos(config('hris.payroll.thirteenth_month_exempt_ceiling')));
        $earnings = $this->earnings->forYear($run->period_end->year);
        $employees = Employee::withTrashed()->with(['department', 'position'])->whereIn('id', $earnings->keys())->get()->keyBy('id');

        DB::transaction(function () use ($run, $calculator, $earnings, $employees) {
            $run->payslips()->delete();

            foreach ($earnings as $employeeId => $basics) {
                $employee = $employees->get($employeeId);
                $result = $calculator->compute($basics);

                if ($employee === null || $result['amount']->isZero()) {
                    continue;
                }

                $warnings = $result['taxable']->isZero() ? null : [
                    "{$result['taxable']->format()} exceeds the tax-exempt ceiling and is taxable; it is settled in the year-end tax annualization.",
                ];

                $payslip = Payslip::query()->create([
                    'payroll_run_id' => $run->id,
                    'employee_id' => $employee->id,
                    'employee_no' => $employee->employee_no,
                    'employee_name' => $employee->full_name,
                    'department' => $employee->department?->name,
                    'position' => $employee->position?->title,
                    'rate_type' => $employee->rate_type->value,
                    'basic_rate' => $employee->basic_rate,
                    'daily_rate' => Money::zero(),
                    'hourly_rate' => Money::zero(),
                    'gross_pay' => $result['amount'],
                    'taxable_income' => $result['taxable'],
                    'total_deductions' => Money::zero(),
                    'net_pay' => $result['amount'],
                    'employer_contributions' => Money::zero(),
                    'attendance' => ['payslips_counted' => count($basics), 'basic_salary_earned' => $result['basic']->toFloat()],
                    'warnings' => $warnings,
                ]);

                $payslip->lines()->createMany(array_filter([
                    ['kind' => 'earning', 'code' => 'THIRTEENTH_MONTH', 'label' => '13th month pay (tax-exempt)', 'amount' => $result['exempt'], 'taxable' => false, 'sort' => 0],
                    $result['taxable']->isZero() ? null
                        : ['kind' => 'earning', 'code' => 'THIRTEENTH_MONTH_TAXABLE', 'label' => '13th month pay (taxable excess)', 'amount' => $result['taxable'], 'taxable' => true, 'sort' => 1],
                ]));
            }

            $payslips = $run->payslips()->get();
            $sum = fn (string $attribute) => $payslips->reduce(fn (Money $carry, Payslip $p) => $carry->plus($p->{$attribute}), Money::zero());

            $run->update([
                'status' => PayrollRunStatus::Computed,
                'computed_at' => now(),
                'employee_count' => $payslips->count(),
                'total_gross' => $sum('gross_pay'),
                'total_deductions' => Money::zero(),
                'total_net' => $sum('net_pay'),
                'total_employer' => Money::zero(),
            ]);
        });

        return $run->refresh();
    }
}
