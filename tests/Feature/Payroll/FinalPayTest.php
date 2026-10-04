<?php

use App\Features\Attendance\Enums\LeaveStatus;
use App\Features\Attendance\Models\LeaveRequest;
use App\Features\Attendance\Models\LeaveType;
use App\Features\Employees\Enums\EmploymentStatus;
use App\Features\Employees\Models\Employee;
use App\Features\Payroll\Enums\LoanStatus;
use App\Features\Payroll\Enums\PayrollRunStatus;
use App\Features\Payroll\Enums\PayrollRunType;
use App\Features\Payroll\Models\FinalPay;
use App\Features\Payroll\Models\Loan;
use App\Features\Payroll\Models\PayrollRun;
use App\Features\Payroll\Models\Payslip;
use App\Shared\Authorization\Role;
use App\Shared\Money\Money;
use Database\Seeders\PayrollSeeder;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-07-10 12:00'));
    $this->seed(PayrollSeeder::class);
    $this->officer = userWithRole(Role::Payroll);
    $this->employee = Employee::factory()->monthly(30000)->create([
        'hired_at' => '2020-01-01', 'status' => EmploymentStatus::Resigned, 'separated_at' => '2026-06-30',
    ]);

    // Twelve finalized semi-monthly payslips, January to June 2026.
    for ($month = 1; $month <= 6; $month++) {
        foreach ([[1, 15], [16, Carbon::create(2026, $month)->daysInMonth]] as [$from, $to]) {
            $run = PayrollRun::query()->create([
                'name' => "Run {$month}-{$from}", 'type' => PayrollRunType::Regular, 'status' => PayrollRunStatus::Finalized,
                'period_start' => Carbon::create(2026, $month, $from), 'period_end' => Carbon::create(2026, $month, $to), 'pay_date' => Carbon::create(2026, $month, $to),
            ]);
            $payslip = Payslip::query()->create([
                'payroll_run_id' => $run->id, 'employee_id' => $this->employee->id, 'employee_no' => $this->employee->employee_no,
                'employee_name' => $this->employee->full_name, 'rate_type' => 'monthly', 'basic_rate' => Money::ofPesos(30000),
                'daily_rate' => Money::ofPesos('1379.31'), 'hourly_rate' => Money::zero(), 'gross_pay' => Money::ofPesos(15000),
                'taxable_income' => Money::ofPesos(13775), 'total_deductions' => Money::ofPesos('1728.70'), 'net_pay' => Money::ofPesos('13271.30'),
                'employer_contributions' => Money::zero(), 'attendance' => [],
            ]);
            foreach (['BASIC' => ['earning', '15000'], 'SSS' => ['deduction', '750'], 'PHILHEALTH' => ['deduction', '375'], 'PAGIBIG' => ['deduction', '100'], 'TAX' => ['deduction', '503.70']] as $code => [$kind, $amount]) {
                $payslip->lines()->create(['kind' => $kind, 'code' => $code, 'label' => $code, 'amount' => Money::ofPesos($amount), 'taxable' => $code === 'BASIC']);
            }
        }
    }

    // 15 VL days, 7.5 used → 7.5 to convert.
    $vl = LeaveType::query()->create(['code' => 'VL', 'name' => 'Vacation Leave', 'is_paid' => true, 'is_convertible' => true, 'days_per_year' => 15]);
    LeaveRequest::query()->create([
        'employee_id' => $this->employee->id, 'leave_type_id' => $vl->id, 'start_date' => '2026-03-02', 'end_date' => '2026-03-10',
        'days' => 7.5, 'status' => LeaveStatus::Approved,
    ]);

    $this->loan = Loan::query()->create([
        'employee_id' => $this->employee->id, 'type' => 'sss_salary', 'principal' => Money::ofPesos(10000),
        'amortization' => Money::ofPesos(1000), 'balance' => Money::ofPesos(4000), 'starts_on' => '2026-01-01', 'status' => LoanStatus::Active,
    ]);
});

it('lists separated employees and prepares their final pay', function () {
    $this->actingAs($this->officer)->get('/payroll/final-pay')->assertOk()->assertSee('Prepare final pay')->assertSee($this->employee->full_name);

    $this->actingAs($this->officer)->post('/payroll/final-pay', ['employee_id' => $this->employee->id])->assertRedirect();

    $finalPay = FinalPay::query()->sole();
    expect($finalPay->separation_date->toDateString())->toBe('2026-06-30')
        ->and($finalPay->status)->toBe(PayrollRunStatus::Draft);

    $this->actingAs($this->officer)->post('/payroll/final-pay', ['employee_id' => $this->employee->id])->assertSessionHasErrors('employee_id');
});

it('computes the final pay from year-to-date payroll (hand-computed)', function () {
    $finalPay = FinalPay::query()->create(['employee_id' => $this->employee->id, 'separation_date' => '2026-06-30', 'status' => PayrollRunStatus::Draft]);
    $finalPay->adjustments()->create(['kind' => 'deduction', 'label' => 'Unreturned ID', 'amount' => Money::ofPesos(200), 'taxable' => false]);

    $this->actingAs($this->officer)->post("/payroll/final-pay/{$finalPay->id}/compute")->assertSessionHas('success');

    $finalPay->refresh();
    $amount = fn (string $code) => collect($finalPay->lines)->where('code', $code)->sum(fn ($l) => (float) $l['amount']);

    expect($amount('THIRTEENTH_MONTH'))->toBe(15000.0)            // 180,000 ÷ 12
        ->and($amount('LEAVE_CONVERSION'))->toBe(10344.83)        // 7.5 × 1,379.31
        ->and($amount('TAX_REFUND'))->toBe(6044.40)               // annual taxable 165,300 < 250,000
        ->and($amount('LOAN_BALANCE'))->toBe(4000.0)
        ->and($amount('OTHER_DEDUCTION'))->toBe(200.0)
        ->and($finalPay->net_pay->toDecimal())->toBe('27189.23')
        ->and($finalPay->status)->toBe(PayrollRunStatus::Computed);

    $this->actingAs($this->officer)->get("/payroll/final-pay/{$finalPay->id}")->assertOk()->assertSee('NET FINAL PAY');
    $this->actingAs($this->officer)->get("/payroll/final-pay/{$finalPay->id}/pdf")->assertOk()->assertHeader('content-type', 'application/pdf');
});

it('settles outstanding loans when finalized and locks the final pay', function () {
    $finalPay = FinalPay::query()->create(['employee_id' => $this->employee->id, 'separation_date' => '2026-06-30', 'status' => PayrollRunStatus::Draft]);

    $this->actingAs($this->officer)->post("/payroll/final-pay/{$finalPay->id}/finalize")->assertSessionHas('error'); // not computed

    $this->actingAs($this->officer)->post("/payroll/final-pay/{$finalPay->id}/compute");
    $this->actingAs($this->officer)->post("/payroll/final-pay/{$finalPay->id}/finalize")->assertSessionHas('success');

    $this->loan->refresh();
    expect($finalPay->fresh()->status)->toBe(PayrollRunStatus::Finalized)
        ->and($this->loan->status)->toBe(LoanStatus::Paid)
        ->and($this->loan->balance->isZero())->toBeTrue()
        ->and($this->loan->payments()->sole()->final_pay_id)->toBe($finalPay->id);

    $this->actingAs($this->officer)->post("/payroll/final-pay/{$finalPay->id}/compute")->assertSessionHas('error');
    $this->actingAs($this->officer)->delete("/payroll/final-pay/{$finalPay->id}")->assertSessionHas('error');
});

it('requires recomputation after adjustments change', function () {
    $finalPay = FinalPay::query()->create(['employee_id' => $this->employee->id, 'separation_date' => '2026-06-30', 'status' => PayrollRunStatus::Draft]);
    $this->actingAs($this->officer)->post("/payroll/final-pay/{$finalPay->id}/compute");

    $this->actingAs($this->officer)->post("/payroll/final-pay/{$finalPay->id}/adjustments", [
        'kind' => 'earning', 'label' => 'Salary differential', 'amount' => '1000', 'taxable' => '1',
    ])->assertSessionHas('success');

    expect($finalPay->fresh()->status)->toBe(PayrollRunStatus::Draft);
    $this->actingAs($this->officer)->post("/payroll/final-pay/{$finalPay->id}/finalize")->assertSessionHas('error');
});

it('restricts final pay to payroll staff', function () {
    $this->actingAs(userWithRole(Role::Hr))->get('/payroll/final-pay')->assertForbidden();
});
