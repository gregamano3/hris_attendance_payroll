<?php

use App\Features\Attendance\Models\Shift;
use App\Features\Attendance\Models\TimeLog;
use App\Features\Employees\Models\CompensationChange;
use App\Features\Employees\Models\Employee;
use App\Features\Payroll\Enums\PayrollRunStatus;
use App\Features\Payroll\Enums\PayrollRunType;
use App\Features\Payroll\Models\PayrollAdjustment;
use App\Features\Payroll\Models\PayrollRun;
use App\Features\Payroll\Models\Payslip;
use App\Shared\Authorization\Role;
use App\Shared\Money\Money;
use Database\Seeders\PayrollSeeder;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-11-05 12:00'));
    $this->seed(PayrollSeeder::class);
    Shift::factory()->default()->create();
    $this->hr = userWithRole(Role::Hr);
    $this->officer = userWithRole(Role::Payroll);
    $this->employee = Employee::factory()->monthly(30000)->create(['hired_at' => '2020-01-01']);

    for ($date = Carbon::parse('2026-10-01'); $date->lte('2026-10-31'); $date->addDay()) {
        if (! $date->isWeekend()) {
            TimeLog::query()->create(['employee_id' => $this->employee->id, 'logged_at' => $date->copy()->setTime(8, 0), 'type' => 'in', 'source' => 'import']);
            TimeLog::query()->create(['employee_id' => $this->employee->id, 'logged_at' => $date->copy()->setTime(17, 0), 'type' => 'out', 'source' => 'import']);
        }
    }
});

function regularRun(string $start, string $end, PayrollRunStatus $status = PayrollRunStatus::Draft): PayrollRun
{
    return PayrollRun::query()->create([
        'name' => "Payroll {$start}", 'type' => PayrollRunType::Regular, 'period_start' => $start,
        'period_end' => $end, 'pay_date' => $end, 'status' => $status,
    ]);
}

it('records the hiring rate and rate edits in the salary history', function () {
    expect(CompensationChange::query()->where('employee_id', $this->employee->id)->sole()->effective_from->toDateString())->toBe('2020-01-01');

    $this->employee->update(['basic_rate' => Money::ofPesos(32000)]);

    $latest = $this->employee->compensationChanges()->first();
    expect($latest->effective_from->toDateString())->toBe('2026-11-05')->and($latest->basic_rate->toDecimal())->toBe('32000.00');
});

it('schedules future changes and applies them when they take effect', function () {
    $this->actingAs($this->hr)->post("/employees/{$this->employee->id}/compensation", [
        'effective_from' => '2027-01-01', 'rate_type' => 'monthly', 'basic_rate' => '35000', 'reason' => 'Annual increase',
    ])->assertSessionHas('success', 'Rate change scheduled for Jan 1, 2027.');

    expect($this->employee->fresh()->basic_rate->toDecimal())->toBe('30000.00');

    $this->travelTo(Carbon::parse('2027-01-01 00:10'));
    $this->artisan('compensation:apply')->assertSuccessful();

    expect($this->employee->fresh()->basic_rate->toDecimal())->toBe('35000.00');
    $this->actingAs($this->hr)->get("/employees/{$this->employee->id}/compensation")->assertOk()->assertSee('Annual increase');
});

it('pays each part of the period at the rate in effect', function () {
    CompensationChange::query()->create(['employee_id' => $this->employee->id, 'effective_from' => '2026-10-09', 'rate_type' => 'monthly', 'basic_rate' => Money::ofPesos(36000), 'reason' => 'Promotion']);
    $run = regularRun('2026-10-01', '2026-10-15');

    $this->actingAs($this->officer)->post("/payroll/runs/{$run->id}/compute");

    expect(Payslip::query()->with('lines')->sole()->amountOf('BASIC')->toDecimal())->toBe('16400.00');
});

it('adds back pay to a draft run for finalized runs after a retroactive increase', function () {
    $finalized = regularRun('2026-10-01', '2026-10-15');
    $this->actingAs($this->officer)->post("/payroll/runs/{$finalized->id}/compute");
    $this->actingAs($this->officer)->post("/payroll/runs/{$finalized->id}/finalize");

    // Retroactive increase approved later, effective Oct 1.
    $this->actingAs($this->hr)->post("/employees/{$this->employee->id}/compensation", [
        'effective_from' => '2026-10-01', 'rate_type' => 'monthly', 'basic_rate' => '36000', 'reason' => 'Wage order (retroactive)',
    ]);

    $target = regularRun('2026-10-16', '2026-10-31');
    $this->actingAs($this->officer)->post("/payroll/runs/{$target->id}/back-pay", ['employee_id' => $this->employee->id, 'since' => '2026-10-01'])
        ->assertSessionHas('success');

    $adjustment = PayrollAdjustment::query()->sole();
    expect($adjustment->code)->toBe('BACK_PAY')
        ->and($adjustment->amount->toDecimal())->toBe('3000.00')   // 18,000 − 15,000
        ->and($adjustment->taxable)->toBeTrue()
        ->and($adjustment->label)->toBe('Back pay — Oct 1–15, 2026');

    // Running it again doesn't duplicate.
    $this->actingAs($this->officer)->post("/payroll/runs/{$target->id}/back-pay", ['employee_id' => $this->employee->id, 'since' => '2026-10-01'])
        ->assertSessionHas('warning');

    $this->actingAs($this->officer)->post("/payroll/runs/{$target->id}/compute");
    $payslip = Payslip::query()->with('lines')->where('payroll_run_id', $target->id)->sole();
    expect($payslip->amountOf('BACK_PAY')->toDecimal())->toBe('3000.00')
        ->and($payslip->amountOf('BASIC')->toDecimal())->toBe('18000.00');
});

it('recovers overpayments after a retroactive decrease', function () {
    $finalized = regularRun('2026-10-01', '2026-10-15');
    $this->actingAs($this->officer)->post("/payroll/runs/{$finalized->id}/compute");
    $this->actingAs($this->officer)->post("/payroll/runs/{$finalized->id}/finalize");

    CompensationChange::query()->create(['employee_id' => $this->employee->id, 'effective_from' => '2026-10-01', 'rate_type' => 'monthly', 'basic_rate' => Money::ofPesos(28000), 'reason' => 'Correction']);

    $target = regularRun('2026-10-16', '2026-10-31');
    $this->actingAs($this->officer)->post("/payroll/runs/{$target->id}/back-pay", ['employee_id' => $this->employee->id, 'since' => '2026-10-01']);

    $adjustment = PayrollAdjustment::query()->sole();
    expect($adjustment->kind)->toBe('deduction')->and($adjustment->amount->toDecimal())->toBe('1000.00');
});
