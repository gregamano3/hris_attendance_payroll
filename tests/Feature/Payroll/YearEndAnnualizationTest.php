<?php

use App\Features\Attendance\Models\Shift;
use App\Features\Attendance\Models\TimeLog;
use App\Features\Employees\Models\Employee;
use App\Features\Payroll\Enums\PayrollRunStatus;
use App\Features\Payroll\Enums\PayrollRunType;
use App\Features\Payroll\Models\PayrollRun;
use App\Features\Payroll\Models\Payslip;
use App\Shared\Authorization\Role;
use App\Shared\Money\Money;
use Database\Seeders\PayrollSeeder;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->travelTo(Carbon::parse('2027-01-05 12:00'));
    $this->seed(PayrollSeeder::class);
    Shift::factory()->default()->create();
    $this->officer = userWithRole(Role::Payroll);
    $this->employee = Employee::factory()->monthly(30000)->create(['hired_at' => '2020-01-01']);

    for ($date = Carbon::parse('2026-12-16'); $date->lte('2026-12-31'); $date->addDay()) {
        if (! $date->isWeekend()) {
            TimeLog::query()->create(['employee_id' => $this->employee->id, 'logged_at' => $date->copy()->setTime(8, 0), 'type' => 'in', 'source' => 'import']);
            TimeLog::query()->create(['employee_id' => $this->employee->id, 'logged_at' => $date->copy()->setTime(17, 0), 'type' => 'out', 'source' => 'import']);
        }
    }

    // Earlier finalized payroll of 2026: taxable ₱300,000, withheld ₱10,000.
    $earlier = PayrollRun::query()->create([
        'name' => 'Earlier', 'type' => PayrollRunType::Regular, 'period_start' => '2026-12-01', 'period_end' => '2026-12-15',
        'pay_date' => '2026-12-15', 'status' => PayrollRunStatus::Finalized,
    ]);
    $payslip = Payslip::query()->create([
        'payroll_run_id' => $earlier->id, 'employee_id' => $this->employee->id, 'employee_no' => $this->employee->employee_no,
        'employee_name' => $this->employee->full_name, 'rate_type' => 'monthly', 'basic_rate' => Money::ofPesos(30000),
        'daily_rate' => Money::zero(), 'hourly_rate' => Money::zero(), 'gross_pay' => Money::ofPesos(300000),
        'taxable_income' => Money::ofPesos(300000), 'total_deductions' => Money::ofPesos(10000), 'net_pay' => Money::ofPesos(290000),
        'employer_contributions' => Money::zero(), 'attendance' => [],
    ]);
    $payslip->lines()->create(['kind' => 'deduction', 'code' => 'TAX', 'label' => 'Tax', 'amount' => Money::ofPesos(10000)]);
});

function decemberRun(bool $annualize): PayrollRun
{
    return PayrollRun::query()->create([
        'name' => 'Dec 16–31', 'type' => PayrollRunType::Regular, 'annualize_tax' => $annualize, 'period_start' => '2026-12-16',
        'period_end' => '2026-12-31', 'pay_date' => '2026-12-31', 'status' => PayrollRunStatus::Draft,
    ]);
}

it('withholds the balance of the annual tax due in the last payroll', function () {
    $run = decemberRun(true);
    $this->actingAs($this->officer)->post("/payroll/runs/{$run->id}/compute");

    $payslip = Payslip::query()->with('lines')->where('payroll_run_id', $run->id)->sole();

    // Annual taxable 300,000 + 13,775 = 313,775 → 15% × 63,775 = 9,566.25; 10,000 already withheld → refund 433.75.
    expect($payslip->amountOf('TAX')->toDecimal())->toBe('0.00')
        ->and($payslip->amountOf('TAX_REFUND')->toDecimal())->toBe('433.75')
        ->and($payslip->gross_pay->toDecimal())->toBe('15433.75')
        ->and($payslip->warnings)->toContain('Year-end annualization: annual taxable ₱313,775.00, tax due ₱9,566.25, withheld before this run ₱10,000.00.');
});

it('withholds the remaining tax when too little was withheld', function () {
    Payslip::query()->first()->lines()->where('code', 'TAX')->update(['amount' => 500000]); // only ₱5,000 withheld

    $run = decemberRun(true);
    $this->actingAs($this->officer)->post("/payroll/runs/{$run->id}/compute");

    expect(Payslip::query()->with('lines')->where('payroll_run_id', $run->id)->sole()->amountOf('TAX')->toDecimal())->toBe('4566.25');
});

it('uses the normal withholding table without annualization', function () {
    $run = decemberRun(false);
    $this->actingAs($this->officer)->post("/payroll/runs/{$run->id}/compute");

    expect(Payslip::query()->with('lines')->where('payroll_run_id', $run->id)->sole()->amountOf('TAX')->toDecimal())->toBe('503.70');
});

it('offers annualization when creating the last run of the year', function () {
    $this->actingAs($this->officer)->post('/payroll/runs', [
        'type' => 'regular', 'period_start' => '2026-12-16', 'period_end' => '2026-12-31', 'pay_date' => '2026-12-31', 'annualize_tax' => '1',
    ])->assertRedirect();

    expect(PayrollRun::query()->latest('id')->first()->annualize_tax)->toBeTrue();
});
