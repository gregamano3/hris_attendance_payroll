<?php

namespace App\Features\Payroll\Compute;

use App\Features\Attendance\Models\AttendanceDay;
use App\Features\Attendance\Queries\AttendanceSummary;
use App\Features\Employees\Enums\RateType;
use App\Features\Employees\Models\Employee;
use App\Features\Employees\Queries\CompensationHistory;
use App\Features\Payroll\Enums\LoanStatus;
use App\Features\Payroll\Enums\PayrollRunStatus;
use App\Features\Payroll\Models\Loan;
use App\Features\Payroll\Models\PayrollAdjustment;
use App\Features\Payroll\Models\PayrollRun;
use App\Features\Payroll\Models\Payslip;
use App\Features\Payroll\Models\RecurringEarning;
use App\Features\Payroll\Queries\AnnualCompensation;
use App\Features\Payroll\Queries\StatutoryRates;
use App\Shared\Money\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * (Re)generates every payslip of a run from attendance, adjustments and the
 * statutory rates effective at the end of the period.
 */
class ComputePayrollRun
{
    public function __construct(
        private AttendanceSummary $attendance,
        private StatutoryRates $rates,
        private CompensationHistory $compensation,
        private AnnualCompensation $annual,
    ) {}

    /** @var Collection<int, array<string, mixed>>|null */
    private ?Collection $yearToDate = null;

    public function handle(PayrollRun $run): PayrollRun
    {
        abort_if($run->isLocked(), 409, 'Finalized payroll runs cannot be recomputed.');

        $period = $run->period();
        $calculator = $this->rates->calculatorFor($period->to);
        $adjustments = $run->adjustments()->get()->groupBy('employee_id');
        $recurring = RecurringEarning::query()->activeDuring($period)->get()->groupBy('employee_id');
        $loans = Loan::query()
            ->where('status', LoanStatus::Active)
            ->whereDate('starts_on', '<=', $period->to)
            ->where('balance', '>', 0)
            ->orderBy('starts_on')
            ->get()
            ->groupBy('employee_id');

        $employees = $this->eligibleEmployees($run);
        // Year-to-date figures from finalized payslips (this run isn't finalized yet).
        $this->yearToDate = $run->annualize_tax
            ? $this->annual->forYear($run->period_end->year)->keyBy(fn (array $row): int => (int) $row['employee']->id)
            : null;

        DB::transaction(function () use ($run, $calculator, $adjustments, $recurring, $loans, $employees) {
            $run->payslips()->delete();
            $total = max(1, $employees->count());

            foreach ($employees->values() as $index => $employee) {
                // Progress is written outside the transaction so the UI can see it.
                if ($index % 10 === 0) {
                    $this->reportProgress($run, (int) floor($index / $total * 100));
                }

                $result = $calculator->compute($this->inputFor($run, $employee, [
                    ...$this->recurringFor($recurring->get($employee->id, collect())),
                    ...$this->adjustmentsFor($adjustments->get($employee->id, collect())),
                    ...$this->loansFor($loans->get($employee->id, collect())),
                ]));

                $this->store($run, $employee, $result);
            }

            $payslips = $run->payslips()->get();

            $run->update([
                'status' => PayrollRunStatus::Computed,
                'progress' => 100,
                'compute_error' => null,
                'computed_at' => now(),
                'employee_count' => $payslips->count(),
                'total_gross' => $this->total($payslips, 'gross_pay'),
                'total_deductions' => $this->total($payslips, 'total_deductions'),
                'total_net' => $this->total($payslips, 'net_pay'),
                'total_employer' => $this->total($payslips, 'employer_contributions'),
            ]);
        });

        return $run->refresh();
    }

    /**
     * Attendance-based earnings of an employee for a run with today's salary
     * history (no adjustments, allowances or loans). Used for back pay.
     */
    public function earningsFor(PayrollRun $run, Employee $employee): PayslipResult
    {
        return $this->rates->calculatorFor($run->period_end)->compute($this->inputFor($run, $employee, []));
    }

    /**
     * @param  list<array{kind: string, label: string, amount: Money, taxable: bool, code?: string}>  $adjustments
     */
    private function inputFor(PayrollRun $run, Employee $employee, array $adjustments): PayslipInput
    {
        $period = $run->period();
        $days = $this->attendance->days($employee->id, $period)
            ->filter(fn (AttendanceDay $day) => $day->date->gte($employee->hired_at)
                && ($employee->separated_at === null || $day->date->lte($employee->separated_at)));
        $segments = $this->compensation->segments($employee, $period);
        $latest = end($segments);

        return new PayslipInput(
            monthlyRated: ($latest ? $latest['rate_type'] : $employee->rate_type->value) === RateType::Monthly->value,
            basicRate: $latest ? $latest['rate'] : $employee->basic_rate,
            days: $days->map(fn (AttendanceDay $day) => $this->toDayData($day))->values()->all(),
            adjustments: $adjustments,
            minimumWageEarner: $employee->is_minimum_wage_earner,
            rateSegments: array_map(fn (array $s) => ['from' => $s['from'], 'rate' => $s['rate']], $segments),
            periodFrom: $period->from->toDateString(),
            periodTo: $period->to->toDateString(),
            annualization: $this->yearToDate === null ? null : [
                'taxable_to_date' => $this->yearToDate->get($employee->id)['taxable'] ?? Money::zero(),
                'withheld_to_date' => $this->yearToDate->get($employee->id)['tax_withheld'] ?? Money::zero(),
            ],
        );
    }

    /**
     * Writes progress on a separate connection so it is visible to the UI
     * while the computation transaction is still open.
     */
    private function reportProgress(PayrollRun $run, int $percent): void
    {
        if (config('database.connections.progress') === null) {
            config(['database.connections.progress' => config('database.connections.'.config('database.default'))]);
        }

        DB::connection('progress')->table('payroll_runs')->where('id', $run->id)->update(['progress' => $percent]);
    }

    /**
     * Employees employed at any point of the period.
     *
     * @return Collection<int, Employee>
     */
    private function eligibleEmployees(PayrollRun $run): Collection
    {
        return Employee::query()
            ->with(['department', 'position', 'branch', 'costCenter'])
            ->whereDate('hired_at', '<=', $run->period_end)
            ->where(fn (Builder $q) => $q->whereNull('separated_at')->orWhereDate('separated_at', '>=', $run->period_start))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    private function toDayData(AttendanceDay $day): DayData
    {
        return new DayData(
            date: $day->date->toDateString(),
            status: $day->status->value,
            isRestDay: $day->is_rest_day,
            holiday: $day->holiday_type?->value,
            workedMinutes: $day->worked_minutes,
            lateMinutes: $day->late_minutes,
            undertimeMinutes: $day->undertime_minutes,
            overtimeMinutes: $day->overtime_minutes,
            nightDiffMinutes: $day->night_diff_minutes,
            paidLeave: (bool) $day->leaveRequest?->leaveType->is_paid,
            leaveFraction: (float) $day->leave_fraction,
            holidayPayEligible: $day->holiday_pay_eligible ?? true,
        );
    }

    /**
     * @param  Collection<int, PayrollAdjustment>  $adjustments
     * @return list<array{kind: string, label: string, amount: Money, taxable: bool}>
     */
    private function adjustmentsFor(Collection $adjustments): array
    {
        return $adjustments->map(fn (PayrollAdjustment $a) => array_filter([
            'kind' => $a->kind,
            'label' => $a->label,
            'amount' => $a->amount,
            'taxable' => $a->taxable,
            'code' => $a->code,
        ], fn ($v) => $v !== null))->values()->all();
    }

    /**
     * @param  Collection<int, RecurringEarning>  $earnings
     * @return list<array{kind: string, label: string, amount: Money, taxable: bool}>
     */
    private function recurringFor(Collection $earnings): array
    {
        return $earnings->map(fn (RecurringEarning $e) => [
            'kind' => PayslipLine::EARNING,
            'label' => $e->label,
            'amount' => $e->amount,
            'taxable' => $e->tax_treatment->isTaxable(),
        ])->values()->all();
    }

    /**
     * @param  Collection<int, Loan>  $loans
     * @return list<array{kind: string, label: string, amount: Money, taxable: bool, code: string}>
     */
    private function loansFor(Collection $loans): array
    {
        return $loans->map(fn (Loan $loan) => [
            'kind' => PayslipLine::DEDUCTION,
            'code' => $loan->lineCode(),
            'label' => $loan->type->label().($loan->reference_no ? " ({$loan->reference_no})" : ''),
            'amount' => $loan->nextDeduction(),
            'taxable' => false,
        ])->values()->all();
    }

    private function store(PayrollRun $run, Employee $employee, PayslipResult $result): void
    {
        $payslip = Payslip::query()->create([
            'payroll_run_id' => $run->id,
            'employee_id' => $employee->id,
            'employee_no' => $employee->employee_no,
            'employee_name' => $employee->full_name,
            'department' => $employee->department?->name,
            'position' => $employee->position?->title,
            'branch' => $employee->branch?->name,
            'cost_center' => $employee->costCenter !== null ? "{$employee->costCenter->code} — {$employee->costCenter->name}" : null,
            'rate_type' => $employee->rate_type->value,
            'basic_rate' => $employee->basic_rate,
            'daily_rate' => $result->dailyRate,
            'hourly_rate' => $result->hourlyRate,
            'is_minimum_wage_earner' => $employee->is_minimum_wage_earner,
            'gross_pay' => $result->grossPay,
            'taxable_income' => $result->taxableIncome,
            'total_deductions' => $result->totalDeductions,
            'net_pay' => $result->netPay,
            'employer_contributions' => $result->employerContributions,
            'attendance' => $result->attendance,
            'warnings' => $result->warnings ?: null,
        ]);

        $payslip->lines()->createMany(array_map(fn (PayslipLine $line, int $i) => [
            'kind' => $line->kind,
            'code' => $line->code,
            'label' => $line->label,
            'quantity' => $line->quantity,
            'unit' => $line->unit,
            'amount' => $line->amount,
            'taxable' => $line->taxable,
            'sort' => $i,
        ], $result->lines, array_keys($result->lines)));
    }

    /**
     * @param  Collection<int, Payslip>  $payslips
     */
    private function total(Collection $payslips, string $attribute): Money
    {
        return $payslips->reduce(fn (Money $carry, Payslip $p) => $carry->plus($p->{$attribute}), Money::zero());
    }
}
