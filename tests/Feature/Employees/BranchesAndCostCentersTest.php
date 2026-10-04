<?php

use App\Features\Attendance\Enums\AttendanceStatus;
use App\Features\Attendance\Enums\HolidayType;
use App\Features\Attendance\Models\AttendanceDay;
use App\Features\Attendance\Models\Holiday;
use App\Features\Attendance\Models\Shift;
use App\Features\Attendance\Queries\AttendanceSummary;
use App\Features\Employees\Models\Branch;
use App\Features\Employees\Models\CostCenter;
use App\Features\Employees\Models\Employee;
use App\Features\Payroll\Enums\PayrollRunStatus;
use App\Features\Payroll\Enums\PayrollRunType;
use App\Features\Payroll\Models\PayrollRun;
use App\Shared\Authorization\Role;
use App\Shared\Period;
use Database\Seeders\PayrollSeeder;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-10-20 12:00'));
    $this->hr = userWithRole(Role::Hr);
    $this->cebu = Branch::query()->create(['code' => 'CEB', 'name' => 'Cebu']);
    $this->manila = Branch::query()->create(['code' => 'MNL', 'name' => 'Manila']);
});

it('manages branches with validated clock restrictions', function () {
    $this->actingAs($this->hr)->post('/branches', [
        'code' => 'dvo', 'name' => 'Davao', 'latitude' => '7.0731', 'longitude' => '125.6128', 'geofence_radius_m' => 150,
        'allowed_ip_ranges' => '203.0.113.10, 198.51.100.0/24',
    ])->assertRedirect('/branches');

    expect(Branch::query()->where('code', 'DVO')->sole()->geofence_radius_m)->toBe(150);

    $this->actingAs($this->hr)->post('/branches', ['code' => 'X', 'name' => 'Bad', 'allowed_ip_ranges' => '999.1.1.1, 10.0.0.0/40'])
        ->assertSessionHasErrors('allowed_ip_ranges');
    $this->actingAs($this->hr)->post('/branches', ['code' => 'Y', 'name' => 'Half', 'latitude' => '7.1'])
        ->assertSessionHasErrors(['longitude', 'geofence_radius_m']);
});

it('applies local holidays only to employees of that branch', function () {
    Shift::factory()->default()->create();
    Holiday::query()->create(['date' => '2026-10-05', 'name' => 'Cebu City Charter Day', 'type' => HolidayType::SpecialNonWorking, 'branch_id' => $this->cebu->id]);
    $cebuano = Employee::factory()->create(['branch_id' => $this->cebu->id]);
    $manileno = Employee::factory()->create(['branch_id' => $this->manila->id]);

    $period = new Period(Carbon::parse('2026-10-05'), Carbon::parse('2026-10-05'));
    app(AttendanceSummary::class)->days($cebuano->id, $period);
    app(AttendanceSummary::class)->days($manileno->id, $period);

    expect(AttendanceDay::query()->where('employee_id', $cebuano->id)->sole()->status)->toBe(AttendanceStatus::Holiday)
        ->and(AttendanceDay::query()->where('employee_id', $manileno->id)->sole()->status)->toBe(AttendanceStatus::Absent);
});

it('allows the same date as a nationwide and a branch holiday only once per scope', function () {
    $payload = ['date' => '2026-08-19', 'name' => 'Quezon City Day', 'type' => 'special_non_working', 'branch_id' => $this->manila->id];

    $this->actingAs($this->hr)->post('/holidays', $payload)->assertSessionHasNoErrors();
    $this->actingAs($this->hr)->post('/holidays', $payload)->assertSessionHasErrors('date');
    $this->actingAs($this->hr)->post('/holidays', [...$payload, 'branch_id' => $this->cebu->id])->assertSessionHasNoErrors();

    $this->actingAs($this->hr)->get('/holidays?year=2026')->assertSee('Manila')->assertSee('Cebu');
});

it('groups payroll by branch and cost center', function () {
    $this->seed(PayrollSeeder::class);
    $sales = CostCenter::query()->create(['code' => 'CC-100', 'name' => 'Sales']);
    $ops = CostCenter::query()->create(['code' => 'CC-200', 'name' => 'Operations']);
    Employee::factory()->monthly(30000)->create(['hired_at' => '2020-01-01', 'branch_id' => $this->cebu->id, 'cost_center_id' => $sales->id]);
    Employee::factory()->monthly(20000)->create(['hired_at' => '2020-01-01', 'branch_id' => $this->manila->id, 'cost_center_id' => $ops->id]);
    $officer = userWithRole(Role::Payroll);
    $run = PayrollRun::query()->create(['name' => 'P', 'type' => PayrollRunType::Regular, 'period_start' => '2026-10-01', 'period_end' => '2026-10-15', 'pay_date' => '2026-10-15', 'status' => PayrollRunStatus::Draft]);

    $this->actingAs($officer)->post("/payroll/runs/{$run->id}/compute");

    $this->actingAs($officer)->get("/payroll/runs/{$run->id}")->assertSee('Cebu · CC-100 — Sales')->assertSee('Manila · CC-200 — Operations');
    expect($this->actingAs($officer)->get("/payroll/runs/{$run->id}/register.csv")->streamedContent())
        ->toContain('Branch,"Cost center"')->toContain('Cebu,"CC-100 — Sales"');
});

it('assigns branches and cost centers on the employee form', function () {
    $costCenter = CostCenter::query()->create(['code' => 'CC-1', 'name' => 'Admin']);
    $employee = Employee::factory()->create();

    $this->actingAs($this->hr)->put("/employees/{$employee->id}", [
        'employee_no' => $employee->employee_no, 'first_name' => 'A', 'last_name' => 'B', 'employment_type' => 'regular', 'status' => 'active',
        'hired_at' => '2020-01-01', 'rate_type' => 'monthly', 'basic_rate' => '20000', 'branch_id' => $this->cebu->id, 'cost_center_id' => $costCenter->id,
    ])->assertSessionHasNoErrors();

    expect($employee->fresh()->branch_id)->toBe($this->cebu->id)->and($employee->fresh()->cost_center_id)->toBe($costCenter->id);
    $this->actingAs($this->hr)->get('/cost-centers')->assertOk()->assertSee('CC-1');
});
