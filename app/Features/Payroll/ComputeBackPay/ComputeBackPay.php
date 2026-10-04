<?php

namespace App\Features\Payroll\ComputeBackPay;

use App\Features\Employees\Models\Employee;
use App\Features\Payroll\Compute\ComputePayrollRun;
use App\Features\Payroll\Compute\PayslipLine as Line;
use App\Features\Payroll\Enums\PayrollRunStatus;
use App\Features\Payroll\Enums\PayrollRunType;
use App\Features\Payroll\Models\PayrollAdjustment;
use App\Features\Payroll\Models\PayrollRun;
use App\Features\Payroll\Models\Payslip;
use App\Features\Payroll\Models\PayslipLine;
use App\Shared\Money\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Back pay after a retroactive salary change: recomputes the attendance-based
 * earnings of each finalized regular run since a date with today's salary
 * history and adds the difference to a draft run as a taxable earning
 * (or a deduction when the change lowered the pay).
 */
class ComputeBackPay
{
    /** Lines that don't depend on the salary rate. */
    private const EXCLUDED = ['ALLOWANCE', 'BACK_PAY', 'BACK_PAY_RECOVERY', 'OTHER_DEDUCTION'];

    public function __construct(private ComputePayrollRun $compute) {}

    /**
     * @return Collection<int, PayrollAdjustment> the adjustments created
     */
    public function handle(PayrollRun $target, Employee $employee, Carbon $since, ?int $userId = null): Collection
    {
        abort_if($target->isLocked() || $target->isComputing(), 409);

        $runs = PayrollRun::query()
            ->where('type', PayrollRunType::Regular)
            ->where('status', PayrollRunStatus::Finalized)
            ->whereDate('period_end', '>=', $since)
            ->whereKeyNot($target->id)
            ->whereHas('payslips', fn ($q) => $q->where('employee_id', $employee->id))
            ->orderBy('period_start')
            ->get();

        $created = collect();

        foreach ($runs as $run) {
            $alreadyApplied = PayrollAdjustment::query()
                ->where(['employee_id' => $employee->id, 'source_run_id' => $run->id])
                ->whereIn('code', ['BACK_PAY', 'BACK_PAY_RECOVERY'])
                ->exists();

            if ($alreadyApplied) {
                continue;
            }

            $payslip = Payslip::query()->with('lines')->where(['payroll_run_id' => $run->id, 'employee_id' => $employee->id])->firstOrFail();
            $paid = $this->rateBasedEarnings($payslip->lines->map(fn (PayslipLine $l) => [$l->kind, $l->code, $l->amount]));
            $due = $this->rateBasedEarnings(collect($this->compute->earningsFor($run, $employee)->lines)->map(fn (Line $l) => [$l->kind, $l->code, $l->amount]));
            $difference = $due->minus($paid);

            if ($difference->isZero()) {
                continue;
            }

            $created->push($target->adjustments()->create([
                'employee_id' => $employee->id,
                'kind' => $difference->isNegative() ? Line::DEDUCTION : Line::EARNING,
                'code' => $difference->isNegative() ? 'BACK_PAY_RECOVERY' : 'BACK_PAY',
                'source_run_id' => $run->id,
                'label' => ($difference->isNegative() ? 'Salary overpayment recovery — ' : 'Back pay — ').$run->period()->label(),
                'amount' => $difference->isNegative() ? $difference->multipliedBy(-1) : $difference,
                'taxable' => ! $difference->isNegative(),
                'created_by' => $userId,
            ]));
        }

        if ($created->isNotEmpty()) {
            $target->update(['status' => PayrollRunStatus::Draft]);
        }

        return $created;
    }

    /**
     * @param  Collection<int, array{0: string, 1: string, 2: Money}>  $lines
     */
    private function rateBasedEarnings(Collection $lines): Money
    {
        return $lines
            ->filter(fn (array $l) => $l[0] === Line::EARNING && ! in_array($l[1], self::EXCLUDED, true))
            ->reduce(fn (Money $c, array $l) => $c->plus($l[2]), Money::zero());
    }
}
