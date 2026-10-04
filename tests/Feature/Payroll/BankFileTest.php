<?php

use App\Features\Employees\Models\Employee;
use App\Features\Payroll\Enums\PayrollRunStatus;
use App\Features\Payroll\Enums\PayrollRunType;
use App\Features\Payroll\Models\PayrollRun;
use App\Features\Payroll\Models\Payslip;
use App\Shared\Authorization\Role;
use App\Shared\Money\Money;

beforeEach(function () {
    $this->officer = userWithRole(Role::Payroll);
    $this->run = PayrollRun::query()->create([
        'name' => 'Payroll', 'type' => PayrollRunType::Regular, 'period_start' => '2026-10-01',
        'period_end' => '2026-10-15', 'pay_date' => '2026-10-15', 'status' => PayrollRunStatus::Finalized,
    ]);

    $this->banked = Employee::factory()->create(['employee_no' => 'EMP-00001', 'bank_account_no' => '001234567890', 'bank_account_name' => 'Juan Dela Cruz']);
    $this->other = Employee::factory()->create(['employee_no' => 'EMP-00002', 'bank_account_no' => '009876543210']);
    $this->cash = Employee::factory()->withoutBankAccount()->create(['employee_no' => 'EMP-00003', 'last_name' => 'Cashpaid']);

    foreach ([[$this->banked, '12345.67'], [$this->other, '10000.00'], [$this->cash, '9000.00']] as [$employee, $net]) {
        Payslip::query()->create([
            'payroll_run_id' => $this->run->id, 'employee_id' => $employee->id, 'employee_no' => $employee->employee_no,
            'employee_name' => $employee->full_name, 'rate_type' => 'monthly', 'basic_rate' => Money::ofPesos(20000),
            'daily_rate' => Money::zero(), 'hourly_rate' => Money::zero(), 'gross_pay' => Money::ofPesos($net), 'taxable_income' => Money::zero(),
            'total_deductions' => Money::zero(), 'net_pay' => Money::ofPesos($net), 'employer_contributions' => Money::zero(), 'attendance' => [],
        ]);
    }
});

it('exports a CSV credit file for bank-paid employees only', function () {
    $content = $this->actingAs($this->officer)->get("/payroll/runs/{$this->run->id}/bank.csv")->assertOk()->streamedContent();
    $rows = array_map('str_getcsv', array_values(array_filter(explode("\n", trim($content)))));

    expect($rows)->toHaveCount(3)
        ->and($rows[0][0])->toBe('Account number')
        ->and(collect($rows)->pluck(0)->all())->toContain('001234567890', '009876543210')
        ->and(collect($rows)->firstWhere(0, '001234567890'))->toBe(['001234567890', 'Juan Dela Cruz', '12345.67', 'EMP-00001', 'BDO', 'PAYROLL 20261015']);
});

it('exports a fixed-width file with matching header and trailer totals', function () {
    $lines = explode("\r\n", trim($this->actingAs($this->officer)->get("/payroll/runs/{$this->run->id}/bank.txt")->assertOk()->streamedContent()));

    expect($lines)->toHaveCount(4)
        ->and(substr($lines[0], 0, 1))->toBe('H')
        ->and(substr($lines[0], 31, 8))->toBe('20261015')
        ->and(substr($lines[0], 39, 6))->toBe('000002')
        ->and(substr($lines[0], 45, 15))->toBe('000000002234567') // 22,345.67
        ->and(strlen($lines[1]))->toBe(91)
        ->and($lines[3])->toBe('T000002000000002234567');

    $amounts = array_map(fn (string $l) => (int) substr($l, 21, 15), array_slice($lines, 1, 2));
    expect(array_sum($amounts))->toBe(2234567);
});

it('lists employees without bank details on the run page', function () {
    $this->actingAs($this->officer)->get("/payroll/runs/{$this->run->id}")
        ->assertOk()
        ->assertSee('1 employee(s) have no bank account')
        ->assertSee('Cashpaid');
});

it('refuses bank files for runs that are not finalized', function () {
    $this->run->update(['status' => PayrollRunStatus::Computed]);

    $this->actingAs($this->officer)->get("/payroll/runs/{$this->run->id}/bank.csv")->assertSessionHas('error');
});

it('validates bank account numbers on the employee form', function () {
    $employee = Employee::factory()->create();
    $payload = [
        'employee_no' => $employee->employee_no, 'first_name' => 'A', 'last_name' => 'B', 'employment_type' => 'regular',
        'status' => 'active', 'hired_at' => '2020-01-01', 'rate_type' => 'monthly', 'basic_rate' => '20000',
    ];

    $this->actingAs(userWithRole(Role::Hr))->put("/employees/{$employee->id}", [...$payload, 'bank_account_no' => '12-34'])
        ->assertSessionHasErrors(['bank_account_no', 'bank_name']);

    $this->actingAs(userWithRole(Role::Hr))->put("/employees/{$employee->id}", [...$payload, 'bank_name' => 'BPI', 'bank_account_no' => '1234-5678-90'])
        ->assertSessionHasNoErrors();
    expect($employee->fresh()->bank_account_no)->toBe('1234567890');
});
