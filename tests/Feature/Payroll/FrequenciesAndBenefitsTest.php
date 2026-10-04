<?php

use App\Features\Attendance\Models\Shift;
use App\Features\Attendance\Models\TimeLog;
use App\Features\Employees\Models\Employee;
use App\Features\Payroll\Enums\PayFrequency;
use App\Features\Payroll\Enums\PayrollRunStatus;
use App\Features\Payroll\Enums\PayrollRunType;
use App\Features\Payroll\Enums\TaxTreatment;
use App\Features\Payroll\Models\DeMinimisBenefit;
use App\Features\Payroll\Models\PayrollAdjustment;
use App\Features\Payroll\Models\PayrollRun;
use App\Features\Payroll\Models\Payslip;
use App\Features\Payroll\Models\RecurringEarning;
use App\Shared\Authorization\Role;
use App\Shared\Money\Money;
use Database\Seeders\PayrollSeeder;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-11-20 12:00'));
    $this->seed(PayrollSeeder::class);
    Shift::factory()->default()->create();
    $this->officer = userWithRole(Role::Payroll);
});

function punchRange(Employee $employee, string $from, string $to): void
{
    for ($d = Carbon::parse($from); $d->lte($to); $d->addDay()) {
        if (! $d->isWeekend()) {
            TimeLog::query()->create(['employee_id' => $employee->id, 'logged_at' => $d->copy()->setTime(8, 0), 'type' => 'in', 'source' => 'import']);
            TimeLog::query()->create(['employee_id' => $employee->id, 'logged_at' => $d->copy()->setTime(17, 0), 'type' => 'out', 'source' => 'import']);
        }
    }
}

function runOf(PayFrequency $frequency, string $start, string $end): PayrollRun
{
    return PayrollRun::query()->create([
        'name' => 'Run', 'type' => PayrollRunType::Regular, 'frequency' => $frequency, 'period_start' => $start,
        'period_end' => $end, 'pay_date' => $end, 'status' => PayrollRunStatus::Draft,
    ]);
}

it('runs monthly payroll with the monthly salary, contributions and tax table', function () {
    $employee = Employee::factory()->monthly(30000)->create(['hired_at' => '2020-01-01', 'pay_frequency' => 'monthly']);
    Employee::factory()->monthly(30000)->create(['hired_at' => '2020-01-01']); // semi-monthly: not in this run
    punchRange($employee, '2026-10-01', '2026-10-31');
    $run = runOf(PayFrequency::Monthly, '2026-10-01', '2026-10-31');

    $this->actingAs($this->officer)->post("/payroll/runs/{$run->id}/compute");

    $payslip = Payslip::query()->with('lines')->where('payroll_run_id', $run->id)->sole();
    expect($payslip->amountOf('BASIC')->toDecimal())->toBe('30000.00')
        ->and($payslip->amountOf('SSS')->toDecimal())->toBe('1500.00')
        ->and($payslip->taxable_income->toDecimal())->toBe('27550.00')   // 30,000 − 1,500 − 750 − 200
        ->and($payslip->amountOf('TAX')->toDecimal())->toBe('1007.55');  // monthly table: 15% × (27,550 − 20,833)
});

it('runs weekly payroll with weekly shares', function () {
    $employee = Employee::factory()->daily(800)->create(['hired_at' => '2020-01-01', 'pay_frequency' => 'weekly']);
    punchRange($employee, '2026-11-02', '2026-11-08');
    $run = runOf(PayFrequency::Weekly, '2026-11-02', '2026-11-08');

    $this->actingAs($this->officer)->post("/payroll/runs/{$run->id}/compute");

    $payslip = Payslip::query()->with('lines')->where('payroll_run_id', $run->id)->sole();
    // 5 days × ₱800; SSS on monthly equivalent 800 × 261 / 12 = 17,400 → MSC 17,500 → ₱875 × 12/52 = ₱201.92
    expect($payslip->amountOf('BASIC')->toDecimal())->toBe('4000.00')
        ->and($payslip->amountOf('SSS')->toDecimal())->toBe('201.92');
});

it('splits de minimis allowances at the ceiling', function () {
    $employee = Employee::factory()->monthly(30000)->create(['hired_at' => '2020-01-01']);
    $rice = DeMinimisBenefit::query()->where('code', 'RICE')->sole(); // ₱2,000 / month → ₱1,000 per semi-monthly run
    RecurringEarning::query()->create([
        'employee_id' => $employee->id, 'label' => 'Rice subsidy', 'amount' => Money::ofPesos(1500),
        'tax_treatment' => TaxTreatment::DeMinimis, 'de_minimis_benefit_id' => $rice->id, 'starts_on' => '2026-01-01',
    ]);
    $run = runOf(PayFrequency::SemiMonthly, '2026-11-01', '2026-11-15');

    $this->actingAs($this->officer)->post("/payroll/runs/{$run->id}/compute");

    $payslip = Payslip::query()->with('lines')->where('payroll_run_id', $run->id)->sole();
    expect($payslip->amountOf('DM_RICE')->toDecimal())->toBe('1000.00')
        ->and($payslip->lines->firstWhere('label', 'Rice subsidy (above de minimis ceiling)')->amount->toDecimal())->toBe('500.00')
        ->and($payslip->lines->firstWhere('label', 'Rice subsidy (above de minimis ceiling)')->taxable)->toBeTrue();
});

it('tracks annual de minimis ceilings across finalized runs', function () {
    $employee = Employee::factory()->monthly(30000)->create(['hired_at' => '2020-01-01']);
    $christmas = DeMinimisBenefit::query()->where('code', 'CHRISTMAS')->sole(); // ₱5,000 / year
    RecurringEarning::query()->create([
        'employee_id' => $employee->id, 'label' => 'Christmas gift', 'amount' => Money::ofPesos(3000),
        'tax_treatment' => TaxTreatment::DeMinimis, 'de_minimis_benefit_id' => $christmas->id, 'starts_on' => '2026-11-01', 'ends_on' => '2026-11-30',
    ]);

    $first = runOf(PayFrequency::SemiMonthly, '2026-11-01', '2026-11-15');
    $this->actingAs($this->officer)->post("/payroll/runs/{$first->id}/compute");
    $this->actingAs($this->officer)->post("/payroll/runs/{$first->id}/finalize");
    $second = runOf(PayFrequency::SemiMonthly, '2026-11-16', '2026-11-30');
    $this->actingAs($this->officer)->post("/payroll/runs/{$second->id}/compute");

    $payslip = Payslip::query()->with('lines')->where('payroll_run_id', $second->id)->sole();
    expect($payslip->amountOf('DM_CHRISTMAS')->toDecimal())->toBe('2000.00'); // 5,000 − 3,000 already exempt
});

it('treats hazard pay as taxable except for minimum wage earners', function () {
    $regular = Employee::factory()->monthly(30000)->create(['hired_at' => '2020-01-01']);
    $mwe = Employee::factory()->daily(695)->create(['hired_at' => '2020-01-01', 'is_minimum_wage_earner' => true]);

    foreach ([$regular, $mwe] as $employee) {
        RecurringEarning::query()->create(['employee_id' => $employee->id, 'label' => 'Hazard pay', 'amount' => Money::ofPesos(1000), 'tax_treatment' => TaxTreatment::Hazard, 'starts_on' => '2026-01-01']);
    }

    $run = runOf(PayFrequency::SemiMonthly, '2026-11-01', '2026-11-15');
    $this->actingAs($this->officer)->post("/payroll/runs/{$run->id}/compute");

    $hazard = fn (Employee $e) => Payslip::query()->with('lines')->where(['payroll_run_id' => $run->id, 'employee_id' => $e->id])->sole()->lines->firstWhere('code', 'HAZARD');
    expect($hazard($regular)->taxable)->toBeTrue()->and($hazard($mwe)->taxable)->toBeFalse();
});

it('distributes service charges equally among the run employees', function () {
    Employee::factory()->count(3)->create(['hired_at' => '2020-01-01']);
    $run = runOf(PayFrequency::SemiMonthly, '2026-11-01', '2026-11-15');
    $this->actingAs($this->officer)->post("/payroll/runs/{$run->id}/compute");

    $this->actingAs($this->officer)->post("/payroll/runs/{$run->id}/service-charge", ['amount' => '1000'])->assertSessionHas('success');

    $shares = PayrollAdjustment::query()->where('code', 'SERVICE_CHARGE')->get()->map(fn ($a) => $a->amount->toDecimal())->sort()->values()->all();
    expect($shares)->toBe(['333.33', '333.33', '333.34'])
        ->and($run->fresh()->status)->toBe(PayrollRunStatus::Draft);

    // A new distribution replaces the previous one.
    $this->actingAs($this->officer)->post("/payroll/runs/{$run->id}/service-charge", ['amount' => '300']);
    expect(PayrollAdjustment::query()->where('code', 'SERVICE_CHARGE')->count())->toBe(3);
});

it('manages de minimis ceilings', function () {
    $rice = DeMinimisBenefit::query()->where('code', 'RICE')->sole();

    $this->actingAs($this->officer)->put("/payroll/de-minimis/{$rice->id}", ['name' => 'Rice subsidy', 'limit_amount' => '2500', 'period' => 'monthly'])
        ->assertSessionHas('success');

    expect($rice->fresh()->limit_amount->toDecimal())->toBe('2500.00');
    $this->actingAs(userWithRole(Role::Hr))->get('/payroll/de-minimis')->assertForbidden();
});
