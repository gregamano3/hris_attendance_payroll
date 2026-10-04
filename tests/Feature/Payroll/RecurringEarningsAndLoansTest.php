<?php

use App\Features\Attendance\Models\Shift;
use App\Features\Employees\Models\Employee;
use App\Features\Payroll\Enums\LoanStatus;
use App\Features\Payroll\Enums\PayrollRunStatus;
use App\Features\Payroll\Enums\PayrollRunType;
use App\Features\Payroll\Enums\TaxTreatment;
use App\Features\Payroll\Models\Loan;
use App\Features\Payroll\Models\PayrollRun;
use App\Features\Payroll\Models\Payslip;
use App\Features\Payroll\Models\RecurringEarning;
use App\Shared\Authorization\Role;
use App\Shared\Money\Money;
use Database\Seeders\PayrollSeeder;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-12-20 12:00'));
    $this->seed(PayrollSeeder::class);
    Shift::factory()->default()->create();
    $this->officer = userWithRole(Role::Payroll);
    $this->employee = Employee::factory()->monthly(30000)->create(['hired_at' => '2020-01-01']);
});

function runFor(string $start, string $end): PayrollRun
{
    return PayrollRun::query()->create([
        'name' => "Payroll {$start}", 'type' => PayrollRunType::Regular, 'period_start' => $start, 'period_end' => $end,
        'pay_date' => $end, 'status' => PayrollRunStatus::Draft,
    ]);
}

function computeAndFinalize(PayrollRun $run, $officer): Payslip
{
    test()->actingAs($officer)->post("/payroll/runs/{$run->id}/compute")->assertSessionHas('success');
    test()->actingAs($officer)->post("/payroll/runs/{$run->id}/finalize")->assertSessionHas('success');

    return $run->payslips()->with('lines')->sole();
}

it('manages recurring allowances', function () {
    $this->actingAs($this->officer)->post('/payroll/allowances', [
        'employee_id' => $this->employee->id, 'label' => 'Rice subsidy', 'amount' => '1000',
        'tax_treatment' => 'de_minimis', 'starts_on' => '2026-11-01',
    ])->assertSessionHas('success');

    $earning = RecurringEarning::query()->sole();
    expect($earning->tax_treatment)->toBe(TaxTreatment::DeMinimis);

    $this->actingAs($this->officer)->patch("/payroll/allowances/{$earning->id}", ['ends_on' => '2026-12-31'])->assertSessionHas('success');
    $this->actingAs($this->officer)->get('/payroll/allowances')->assertOk()->assertSee('Rice subsidy');
});

it('applies active recurring allowances with their tax treatment', function () {
    RecurringEarning::query()->create(['employee_id' => $this->employee->id, 'label' => 'Rice subsidy', 'amount' => Money::ofPesos(1000), 'tax_treatment' => TaxTreatment::DeMinimis, 'starts_on' => '2026-01-01']);
    RecurringEarning::query()->create(['employee_id' => $this->employee->id, 'label' => 'Transport', 'amount' => Money::ofPesos(500), 'tax_treatment' => TaxTreatment::Taxable, 'starts_on' => '2026-01-01']);
    RecurringEarning::query()->create(['employee_id' => $this->employee->id, 'label' => 'Expired', 'amount' => Money::ofPesos(999), 'tax_treatment' => TaxTreatment::Taxable, 'starts_on' => '2026-01-01', 'ends_on' => '2026-11-30']);

    $run = runFor('2026-12-01', '2026-12-15');
    $this->actingAs($this->officer)->post("/payroll/runs/{$run->id}/compute");
    $payslip = $run->payslips()->with('lines')->sole();

    $allowances = $payslip->lines->where('code', 'ALLOWANCE');
    expect($allowances->pluck('label')->sort()->values()->all())->toBe(['Rice subsidy', 'Transport'])
        ->and($allowances->firstWhere('label', 'Rice subsidy')->taxable)->toBeFalse()
        ->and($allowances->firstWhere('label', 'Transport')->taxable)->toBeTrue();
});

it('creates loans with the full balance', function () {
    $this->actingAs($this->officer)->post('/payroll/loans', [
        'employee_id' => $this->employee->id, 'type' => 'sss_salary', 'reference_no' => 'SL-123',
        'principal' => '5000', 'amortization' => '2000', 'starts_on' => '2026-11-01',
    ])->assertSessionHas('success');

    $loan = Loan::query()->sole();
    expect($loan->balance->toDecimal())->toBe('5000.00')->and($loan->status)->toBe(LoanStatus::Active);

    $this->actingAs($this->officer)->post('/payroll/loans', [
        'employee_id' => $this->employee->id, 'type' => 'company', 'principal' => '100', 'amortization' => '200', 'starts_on' => '2026-11-01',
    ])->assertSessionHasErrors('amortization');
});

it('deducts amortizations and reduces the balance only when finalized', function () {
    $loan = Loan::query()->create([
        'employee_id' => $this->employee->id, 'type' => 'pagibig_mpl', 'principal' => Money::ofPesos(5000),
        'amortization' => Money::ofPesos(2000), 'balance' => Money::ofPesos(5000), 'starts_on' => '2026-11-01', 'status' => LoanStatus::Active,
    ]);

    $first = runFor('2026-11-01', '2026-11-15');
    $this->actingAs($this->officer)->post("/payroll/runs/{$first->id}/compute");
    expect($loan->fresh()->balance->toDecimal())->toBe('5000.00'); // computed, not finalized

    $payslip = computeAndFinalize($first, $this->officer);
    expect($payslip->amountOf($loan->lineCode())->toDecimal())->toBe('2000.00')
        ->and($loan->fresh()->balance->toDecimal())->toBe('3000.00');

    computeAndFinalize(runFor('2026-11-16', '2026-11-30'), $this->officer);
    $last = computeAndFinalize(runFor('2026-12-01', '2026-12-15'), $this->officer);

    $loan->refresh();
    expect($last->amountOf($loan->lineCode())->toDecimal())->toBe('1000.00') // capped at the balance
        ->and($loan->balance->isZero())->toBeTrue()
        ->and($loan->status)->toBe(LoanStatus::Paid)
        ->and($loan->payments()->count())->toBe(3);

    // Paid loans are no longer deducted.
    $next = runFor('2026-12-16', '2026-12-31');
    $this->actingAs($this->officer)->post("/payroll/runs/{$next->id}/compute");
    expect($next->payslips()->with('lines')->sole()->amountOf($loan->lineCode())->isZero())->toBeTrue();

    $this->actingAs($this->officer)->get("/payroll/loans/{$loan->id}")->assertOk()->assertSee('Payroll 2026-12-01');
});

it('stops deducting cancelled loans and does not start before the start date', function () {
    $future = Loan::query()->create([
        'employee_id' => $this->employee->id, 'type' => 'company', 'principal' => Money::ofPesos(1000),
        'amortization' => Money::ofPesos(500), 'balance' => Money::ofPesos(1000), 'starts_on' => '2027-01-01', 'status' => LoanStatus::Active,
    ]);
    $cancelled = Loan::query()->create([
        'employee_id' => $this->employee->id, 'type' => 'cash_advance', 'principal' => Money::ofPesos(1000),
        'amortization' => Money::ofPesos(500), 'balance' => Money::ofPesos(1000), 'starts_on' => '2026-01-01', 'status' => LoanStatus::Active,
    ]);
    $this->actingAs($this->officer)->patch("/payroll/loans/{$cancelled->id}/cancel")->assertSessionHas('success');

    $run = runFor('2026-12-01', '2026-12-15');
    $this->actingAs($this->officer)->post("/payroll/runs/{$run->id}/compute");

    expect($run->payslips()->with('lines')->sole()->lines->filter(fn ($l) => str_starts_with($l->code, 'LOAN_')))->toBeEmpty();
});

it('restricts loan management to payroll managers', function () {
    $this->actingAs(userWithRole(Role::Hr))->get('/payroll/loans')->assertForbidden();
    $this->actingAs(userWithRole(Role::Employee))->post('/payroll/loans', [])->assertForbidden();
});
