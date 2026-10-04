<?php

use App\Features\Employees\Models\Employee;
use App\Features\Payroll\Enums\PayrollRunStatus;
use App\Features\Payroll\Models\PayrollRun;
use App\Features\Payroll\Models\Payslip;
use App\Shared\Authorization\Role;
use App\Shared\Money\Money;

function makePayslip(Employee $employee, PayrollRunStatus $status): Payslip
{
    $run = PayrollRun::query()->create([
        'name' => 'Payroll', 'period_start' => '2026-09-16', 'period_end' => '2026-09-30',
        'pay_date' => '2026-09-30', 'status' => $status,
    ]);

    return Payslip::query()->create([
        'payroll_run_id' => $run->id, 'employee_id' => $employee->id, 'employee_no' => $employee->employee_no,
        'employee_name' => $employee->full_name, 'rate_type' => 'monthly', 'basic_rate' => Money::ofPesos(20000),
        'daily_rate' => Money::ofPesos(919.54), 'hourly_rate' => Money::ofPesos(114.94), 'gross_pay' => Money::ofPesos(10000),
        'taxable_income' => Money::ofPesos(9000), 'total_deductions' => Money::ofPesos(1000), 'net_pay' => Money::ofPesos(9000),
        'employer_contributions' => Money::ofPesos(1500), 'attendance' => ['days_worked' => 11],
    ]);
}

beforeEach(function () {
    $this->user = userWithRole(Role::Employee);
    $this->employee = Employee::factory()->forUser($this->user)->create();
});

it('shows employees their finalized payslips', function () {
    $payslip = makePayslip($this->employee, PayrollRunStatus::Finalized);

    $this->actingAs($this->user)->get('/payroll/my-payslips')->assertOk()->assertSee('₱9,000.00');
    $this->actingAs($this->user)->get("/payroll/payslips/{$payslip->id}")->assertOk()->assertSee('NET PAY');
    $this->actingAs($this->user)->get("/payroll/payslips/{$payslip->id}/pdf")->assertOk()->assertHeader('content-type', 'application/pdf');
});

it('hides draft payslips from employees', function () {
    $payslip = makePayslip($this->employee, PayrollRunStatus::Computed);

    $this->actingAs($this->user)->get('/payroll/my-payslips')->assertOk()->assertDontSee('₱9,000.00');
    $this->actingAs($this->user)->get("/payroll/payslips/{$payslip->id}")->assertForbidden();
});

it('forbids employees from viewing other payslips', function () {
    $payslip = makePayslip(Employee::factory()->create(), PayrollRunStatus::Finalized);

    $this->actingAs($this->user)->get("/payroll/payslips/{$payslip->id}")->assertForbidden();
});

it('lets payroll staff view any payslip', function () {
    $payslip = makePayslip($this->employee, PayrollRunStatus::Computed);

    $this->actingAs(userWithRole(Role::Payroll))->get("/payroll/payslips/{$payslip->id}")->assertOk()->assertSee('Draft');
});
