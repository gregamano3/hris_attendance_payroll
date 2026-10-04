<?php

use App\Features\Attendance\Compute\AccrueLeaveCredits;
use App\Features\Attendance\Enums\AttendanceStatus;
use App\Features\Attendance\Enums\LeaveStatus;
use App\Features\Attendance\Models\AttendanceDay;
use App\Features\Attendance\Models\LeaveCredit;
use App\Features\Attendance\Models\LeaveRequest;
use App\Features\Attendance\Models\LeaveType;
use App\Features\Attendance\Models\Shift;
use App\Features\Attendance\Models\TimeLog;
use App\Features\Attendance\Queries\LeaveBalances;
use App\Features\Employees\Models\Employee;
use App\Shared\Authorization\Role;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-10-20 12:00'));
    Shift::factory()->default()->create();
    $this->vl = LeaveType::query()->create(['code' => 'VL', 'name' => 'Vacation Leave', 'is_paid' => true, 'days_per_year' => 0, 'accrual_per_month' => 1.25, 'carry_over_cap' => 5]);
    $this->user = userWithRole(Role::Employee);
    $this->employee = Employee::factory()->forUser($this->user)->create(['hired_at' => '2020-01-01']);
});

it('files half-day leaves as half a day on a single date', function () {
    app(AccrueLeaveCredits::class)->handle(Carbon::parse('2026-09-30'));

    $this->actingAs($this->user)->post('/leaves', [
        'leave_type_id' => $this->vl->id, 'start_date' => '2026-10-05', 'end_date' => '2026-10-05', 'day_part' => 'am',
    ])->assertSessionHas('success', 'Leave request for 0.5 day(s) submitted.');

    expect((float) LeaveRequest::query()->sole()->days)->toBe(0.5);

    $this->actingAs($this->user)->post('/leaves', [
        'leave_type_id' => $this->vl->id, 'start_date' => '2026-10-07', 'end_date' => '2026-10-08', 'day_part' => 'pm',
    ])->assertSessionHasErrors('day_part');
});

it('computes attendance for an approved half-day leave', function () {
    LeaveRequest::query()->create([
        'employee_id' => $this->employee->id, 'leave_type_id' => $this->vl->id, 'start_date' => '2026-10-05',
        'end_date' => '2026-10-05', 'day_part' => 'am', 'days' => 0.5, 'status' => LeaveStatus::Approved,
    ]);
    TimeLog::query()->create(['employee_id' => $this->employee->id, 'logged_at' => '2026-10-05 12:30', 'type' => 'in', 'source' => 'web']);
    TimeLog::query()->create(['employee_id' => $this->employee->id, 'logged_at' => '2026-10-05 17:00', 'type' => 'out', 'source' => 'web']);

    $day = AttendanceDay::query()->whereDate('date', '2026-10-05')->sole();
    expect($day->status)->toBe(AttendanceStatus::Present)
        ->and($day->worked_minutes)->toBe(240)
        ->and($day->late_minutes)->toBe(0)
        ->and((float) $day->leave_fraction)->toBe(0.5);
});

it('accrues monthly credits idempotently from the hire month', function () {
    $newHire = Employee::factory()->create(['hired_at' => '2026-07-20']); // counts from August

    $accrue = app(AccrueLeaveCredits::class);
    $accrue->handle(Carbon::parse('2026-09-30'));
    $accrue->handle(Carbon::parse('2026-09-30')); // no double credit

    $credit = fn (Employee $e) => LeaveCredit::query()->where(['employee_id' => $e->id, 'leave_type_id' => $this->vl->id, 'year' => 2026])->sole();

    expect((float) $credit($this->employee)->earned)->toBe(11.25)   // Jan–Sep
        ->and((float) $credit($newHire)->earned)->toBe(2.5)          // Aug–Sep
        ->and($credit($this->employee)->accrued_through_month)->toBe(9);

    $accrue->handle(Carbon::parse('2026-10-31'));
    expect((float) $credit($this->employee)->earned)->toBe(12.5);
});

it('carries unused credits over up to the cap', function () {
    LeaveCredit::query()->create(['employee_id' => $this->employee->id, 'leave_type_id' => $this->vl->id, 'year' => 2025, 'earned' => 15, 'accrued_through_month' => 12]);
    LeaveRequest::query()->create([
        'employee_id' => $this->employee->id, 'leave_type_id' => $this->vl->id, 'start_date' => '2025-05-05',
        'end_date' => '2025-05-07', 'days' => 3, 'status' => LeaveStatus::Approved,
    ]);

    app(AccrueLeaveCredits::class)->handle(Carbon::parse('2026-01-31'));

    $credit = LeaveCredit::query()->where(['employee_id' => $this->employee->id, 'year' => 2026])->sole();
    expect((float) $credit->carried_over)->toBe(5.0)   // 12 unused, capped at 5
        ->and((float) $credit->earned)->toBe(1.25)
        ->and(app(LeaveBalances::class)->forEmployee($this->employee->id, 2026)->firstWhere('type.code', 'VL')['remaining'])->toBe(6.25);
});

it('limits requests to accrued credits', function () {
    app(AccrueLeaveCredits::class)->handle(Carbon::parse('2026-01-31')); // 1.25 days

    $this->actingAs($this->user)->post('/leaves', [
        'leave_type_id' => $this->vl->id, 'start_date' => '2026-10-05', 'end_date' => '2026-10-06',
    ])->assertSessionHasErrors('end_date');
});

it('runs the accrual command', function () {
    $this->artisan('leaves:accrue', ['--through' => '2026-03'])->expectsOutputToContain('through March 2026')->assertSuccessful();

    expect((float) LeaveCredit::query()->where('employee_id', $this->employee->id)->sole()->earned)->toBe(3.75);
});

it('lets HR configure leave types', function () {
    $this->actingAs(userWithRole(Role::Hr))->put("/leave-types/{$this->vl->id}", [
        'code' => 'vl', 'name' => 'Vacation Leave', 'days_per_year' => 0, 'accrual_per_month' => '1.5', 'carry_over_cap' => 10,
        'is_paid' => '1', 'is_convertible' => '1',
    ])->assertSessionHas('success');

    $this->vl->refresh();
    expect((float) $this->vl->accrual_per_month)->toBe(1.5)->and($this->vl->is_convertible)->toBeTrue();

    $this->actingAs($this->user)->get('/leave-types')->assertForbidden();
});
