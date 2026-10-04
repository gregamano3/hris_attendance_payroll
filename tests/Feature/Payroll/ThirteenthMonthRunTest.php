<?php

use App\Features\Employees\Models\Employee;
use App\Features\Payroll\Enums\PayrollRunStatus;
use App\Features\Payroll\Enums\PayrollRunType;
use App\Features\Payroll\Models\PayrollRun;
use App\Features\Payroll\Models\Payslip;
use App\Shared\Authorization\Role;
use App\Shared\Money\Money;

beforeEach(fn () => $this->officer = userWithRole(Role::Payroll));

/**
 * A regular run with one payslip whose lines are [code => pesos].
 *
 * @param  array<string, string>  $lines
 */
function regularPayslip(Employee $employee, string $periodEnd, array $lines, PayrollRunStatus $status = PayrollRunStatus::Finalized): void
{
    $run = PayrollRun::query()->firstOrCreate(
        ['period_start' => substr($periodEnd, 0, 8).(str_ends_with($periodEnd, '15') ? '01' : '16'), 'period_end' => $periodEnd],
        ['name' => "Payroll {$periodEnd}", 'type' => PayrollRunType::Regular, 'pay_date' => $periodEnd, 'status' => $status],
    );

    $payslip = Payslip::query()->create([
        'payroll_run_id' => $run->id, 'employee_id' => $employee->id, 'employee_no' => $employee->employee_no,
        'employee_name' => $employee->full_name, 'rate_type' => 'monthly', 'basic_rate' => Money::ofPesos(30000),
        'daily_rate' => Money::zero(), 'hourly_rate' => Money::zero(), 'gross_pay' => Money::zero(), 'taxable_income' => Money::zero(),
        'total_deductions' => Money::zero(), 'net_pay' => Money::zero(), 'employer_contributions' => Money::zero(), 'attendance' => [],
    ]);

    foreach ($lines as $code => $amount) {
        $payslip->lines()->create(['kind' => 'earning', 'code' => $code, 'label' => $code, 'amount' => Money::ofPesos($amount)]);
    }
}

it('creates one 13th month run per year', function () {
    $this->actingAs($this->officer)->post('/payroll/runs', ['type' => 'thirteenth_month', 'year' => 2026, 'pay_date' => '2026-12-18'])
        ->assertRedirect();

    $run = PayrollRun::query()->firstOrFail();
    expect($run->type)->toBe(PayrollRunType::ThirteenthMonth)
        ->and($run->name)->toBe('13th month pay 2026')
        ->and($run->period_start->toDateString())->toBe('2026-01-01')
        ->and($run->period_end->toDateString())->toBe('2026-12-31');

    $this->actingAs($this->officer)->post('/payroll/runs', ['type' => 'thirteenth_month', 'year' => 2026, 'pay_date' => '2026-12-20'])
        ->assertSessionHasErrors('year');
});

it('computes 1/12 of finalized basic salary only', function () {
    $employee = Employee::factory()->create();
    regularPayslip($employee, '2026-01-15', ['BASIC' => '15000', 'ABSENCES' => '-1000', 'OT_REGULAR' => '5000', 'ALLOWANCE' => '2000']);
    regularPayslip($employee, '2026-01-31', ['BASIC' => '15000', 'PAID_LEAVE' => '0', 'NIGHT_DIFF' => '300']);
    regularPayslip($employee, '2026-02-15', ['BASIC' => '15000'], PayrollRunStatus::Computed); // not finalized
    regularPayslip($employee, '2025-12-31', ['BASIC' => '15000']); // previous year

    $run = PayrollRun::query()->create([
        'name' => '13th month pay 2026', 'type' => PayrollRunType::ThirteenthMonth, 'period_start' => '2026-01-01',
        'period_end' => '2026-12-31', 'pay_date' => '2026-12-18', 'status' => PayrollRunStatus::Draft,
    ]);

    $this->actingAs($this->officer)->post("/payroll/runs/{$run->id}/compute")->assertSessionHas('success');

    $payslip = $run->payslips()->with('lines')->sole();
    expect($payslip->gross_pay->toDecimal())->toBe('2416.67') // 29,000 / 12
        ->and($payslip->net_pay->toDecimal())->toBe('2416.67')
        ->and($payslip->amountOf('THIRTEENTH_MONTH')->toDecimal())->toBe('2416.67')
        ->and($payslip->total_deductions->isZero())->toBeTrue()
        ->and($run->fresh()->status)->toBe(PayrollRunStatus::Computed)
        ->and($run->fresh()->total_net->toDecimal())->toBe('2416.67');
});

it('splits the taxable excess over the exemption ceiling', function () {
    config(['hris.payroll.thirteenth_month_exempt_ceiling' => 1000]);
    regularPayslip(Employee::factory()->create(), '2026-03-15', ['BASIC' => '24000']);

    $run = PayrollRun::query()->create([
        'name' => '13th', 'type' => PayrollRunType::ThirteenthMonth, 'period_start' => '2026-01-01',
        'period_end' => '2026-12-31', 'pay_date' => '2026-12-18', 'status' => PayrollRunStatus::Draft,
    ]);
    $this->actingAs($this->officer)->post("/payroll/runs/{$run->id}/compute");

    $payslip = $run->payslips()->with('lines')->sole();
    expect($payslip->amountOf('THIRTEENTH_MONTH')->toDecimal())->toBe('1000.00')
        ->and($payslip->amountOf('THIRTEENTH_MONTH_TAXABLE')->toDecimal())->toBe('1000.00')
        ->and($payslip->taxable_income->toDecimal())->toBe('1000.00')
        ->and($payslip->warnings)->toHaveCount(1);
});

it('does not count 13th month runs as overlapping regular runs', function () {
    $this->actingAs($this->officer)->post('/payroll/runs', ['type' => 'thirteenth_month', 'year' => 2026, 'pay_date' => '2026-12-18']);

    $this->actingAs($this->officer)->post('/payroll/runs', [
        'type' => 'regular', 'period_start' => '2026-12-01', 'period_end' => '2026-12-15', 'pay_date' => '2026-12-15',
    ])->assertSessionHasNoErrors();

    expect(PayrollRun::query()->count())->toBe(2);
});
