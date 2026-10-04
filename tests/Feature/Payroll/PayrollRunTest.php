<?php

use App\Features\Attendance\Models\Shift;
use App\Features\Attendance\Models\TimeLog;
use App\Features\Employees\Models\Employee;
use App\Features\Payroll\Enums\PayrollRunStatus;
use App\Features\Payroll\Models\PayrollRun;
use App\Features\Payroll\Models\Payslip;
use App\Shared\Authorization\Role;
use Database\Seeders\PayrollSeeder;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-10-20 12:00'));
    $this->seed(PayrollSeeder::class);
    Shift::factory()->default()->create();
    $this->officer = userWithRole(Role::Payroll);
});

/**
 * Full attendance Oct 1–15, 2026 (11 work days) except the given dates.
 *
 * @param  list<string>  $skip
 */
function punchFullPeriod(Employee $employee, array $skip = []): void
{
    for ($date = Carbon::parse('2026-10-01'); $date->lte('2026-10-15'); $date->addDay()) {
        if ($date->isWeekend() || in_array($date->toDateString(), $skip, true)) {
            continue;
        }

        TimeLog::query()->create(['employee_id' => $employee->id, 'logged_at' => $date->copy()->setTime(8, 0), 'type' => 'in', 'source' => 'import']);
        TimeLog::query()->create(['employee_id' => $employee->id, 'logged_at' => $date->copy()->setTime(17, 0), 'type' => 'out', 'source' => 'import']);
    }
}

function createRun(array $overrides = []): PayrollRun
{
    return PayrollRun::query()->create([
        'name' => 'Payroll Oct 1–15, 2026', 'period_start' => '2026-10-01', 'period_end' => '2026-10-15',
        'pay_date' => '2026-10-15', 'status' => PayrollRunStatus::Draft, ...$overrides,
    ]);
}

it('creates a run for the next semi-monthly period', function () {
    $this->actingAs($this->officer)->get('/payroll/runs/create')->assertOk()->assertSee('2026-10-16');

    $this->actingAs($this->officer)->post('/payroll/runs', [
        'period_start' => '2026-10-01', 'period_end' => '2026-10-15', 'pay_date' => '2026-10-15',
    ])->assertRedirect();

    expect(PayrollRun::query()->first()->name)->toBe('Payroll Oct 1–15, 2026');
});

it('rejects overlapping runs', function () {
    createRun();

    $this->actingAs($this->officer)->post('/payroll/runs', [
        'period_start' => '2026-10-10', 'period_end' => '2026-10-25', 'pay_date' => '2026-10-25',
    ])->assertSessionHasErrors('period_start');
});

it('computes payslips from attendance and statutory tables', function () {
    $employee = Employee::factory()->monthly(30000)->create(['hired_at' => '2020-01-01']);
    punchFullPeriod($employee, skip: ['2026-10-06']);
    $run = createRun();

    $this->actingAs($this->officer)->post("/payroll/runs/{$run->id}/compute")->assertSessionHas('success');

    $run->refresh();
    $payslip = Payslip::query()->with('lines')->where('employee_id', $employee->id)->firstOrFail();

    expect($run->status)->toBe(PayrollRunStatus::Computed)
        ->and($run->employee_count)->toBe(1)
        ->and($payslip->amountOf('BASIC')->toDecimal())->toBe('15000.00')
        ->and($payslip->amountOf('ABSENCES')->toDecimal())->toBe('-1379.31')
        ->and($payslip->gross_pay->toDecimal())->toBe('13620.69')
        ->and($payslip->amountOf('SSS')->toDecimal())->toBe('750.00')
        ->and($payslip->amountOf('TAX')->toDecimal())->toBe('296.80') // 15% × (13,620.69 − 1,225 − 10,417)
        ->and($payslip->net_pay->toDecimal())->toBe('12098.89')
        ->and($run->total_net->toDecimal())->toBe('12098.89')
        ->and($payslip->attendance['days_absent'])->toBe(1);
});

it('includes only employees employed during the period', function () {
    Employee::factory()->create(['hired_at' => '2026-11-01']);
    Employee::factory()->separated()->create(['hired_at' => '2020-01-01', 'separated_at' => '2026-09-30']);
    $leaver = Employee::factory()->separated()->create(['hired_at' => '2020-01-01', 'separated_at' => '2026-10-05']);
    $run = createRun();

    $this->actingAs($this->officer)->post("/payroll/runs/{$run->id}/compute");

    expect(Payslip::query()->pluck('employee_id')->all())->toBe([$leaver->id]);
});

it('applies adjustments after recomputing and requires recomputation before finalizing', function () {
    $employee = Employee::factory()->monthly(30000)->create(['hired_at' => '2020-01-01']);
    punchFullPeriod($employee);
    $run = createRun();
    $this->actingAs($this->officer)->post("/payroll/runs/{$run->id}/compute");

    $this->actingAs($this->officer)->post("/payroll/runs/{$run->id}/adjustments", [
        'employee_id' => $employee->id, 'kind' => 'earning', 'label' => 'Rice subsidy', 'amount' => '1000', 'taxable' => '0',
    ])->assertSessionHas('success');

    expect($run->fresh()->status)->toBe(PayrollRunStatus::Draft);
    $this->actingAs($this->officer)->post("/payroll/runs/{$run->id}/finalize")->assertSessionHas('error');

    $this->actingAs($this->officer)->post("/payroll/runs/{$run->id}/compute");
    $payslip = Payslip::query()->with('lines')->firstOrFail();

    expect($payslip->gross_pay->toDecimal())->toBe('16000.00')
        ->and($payslip->taxable_income->toDecimal())->toBe('13775.00');
});

it('finalizes and locks a run', function () {
    $employee = Employee::factory()->create(['hired_at' => '2020-01-01']);
    $run = createRun();
    $this->actingAs($this->officer)->post("/payroll/runs/{$run->id}/compute");

    $this->actingAs($this->officer)->post("/payroll/runs/{$run->id}/finalize")->assertSessionHas('success');

    $run->refresh();
    expect($run->status)->toBe(PayrollRunStatus::Finalized)->and($run->finalized_by)->toBe($this->officer->id);

    $this->actingAs($this->officer)->post("/payroll/runs/{$run->id}/compute")->assertSessionHas('error');
    $this->actingAs($this->officer)->delete("/payroll/runs/{$run->id}")->assertSessionHas('error');
    $this->actingAs($this->officer)->post("/payroll/runs/{$run->id}/adjustments", [
        'employee_id' => $employee->id, 'kind' => 'earning', 'label' => 'Late bonus', 'amount' => '1',
    ])->assertStatus(409);
});

it('exports the payroll register', function () {
    Employee::factory()->monthly(20000)->create(['hired_at' => '2020-01-01', 'employee_no' => 'EMP-77777']);
    $run = createRun();
    $this->actingAs($this->officer)->post("/payroll/runs/{$run->id}/compute");

    $csv = $this->actingAs($this->officer)->get("/payroll/runs/{$run->id}/register.csv")->assertOk()->streamedContent();

    expect($csv)->toContain('Employee no.')->toContain('EMP-77777');
});

it('deletes draft runs', function () {
    $run = createRun();

    $this->actingAs($this->officer)->delete("/payroll/runs/{$run->id}")->assertRedirect('/payroll/runs');
    $this->assertModelMissing($run);
});

it('restricts payroll management by role', function () {
    $run = createRun();

    $this->actingAs(userWithRole(Role::Hr))->get('/payroll/runs')->assertForbidden();
    $this->actingAs(userWithRole(Role::Employee))->post("/payroll/runs/{$run->id}/compute")->assertForbidden();
});
