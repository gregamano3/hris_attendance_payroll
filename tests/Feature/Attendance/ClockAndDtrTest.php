<?php

use App\Features\Attendance\Enums\AttendanceStatus;
use App\Features\Attendance\Enums\TimeLogSource;
use App\Features\Attendance\Enums\TimeLogType;
use App\Features\Attendance\Models\AttendanceDay;
use App\Features\Attendance\Models\Shift;
use App\Features\Attendance\Models\TimeLog;
use App\Features\Employees\Models\Employee;
use App\Shared\Authorization\Role;
use Illuminate\Support\Carbon;

beforeEach(function () {
    Shift::factory()->default()->create();
    $this->user = userWithRole(Role::Employee);
    $this->employee = Employee::factory()->forUser($this->user)->create();
});

it('clocks in and then out, computing the attendance day', function () {
    $this->travelTo(Carbon::parse('2026-10-05 07:55'));
    $this->actingAs($this->user)->post('/attendance/clock')->assertSessionHas('success');

    $this->travelTo(Carbon::parse('2026-10-05 17:05'));
    $this->actingAs($this->user)->post('/attendance/clock')->assertSessionHas('success');

    $logs = TimeLog::query()->where('employee_id', $this->employee->id)->orderBy('logged_at')->get();
    expect($logs->pluck('type')->all())->toBe([TimeLogType::In, TimeLogType::Out])
        ->and($logs->first()->source)->toBe(TimeLogSource::Web);

    $day = AttendanceDay::query()->where('employee_id', $this->employee->id)->whereDate('date', '2026-10-05')->firstOrFail();
    expect($day->status)->toBe(AttendanceStatus::Present)
        ->and($day->worked_minutes)->toBe(480);
});

it('prevents double punches within a minute', function () {
    $this->travelTo(Carbon::parse('2026-10-05 08:00'));
    $this->actingAs($this->user)->post('/attendance/clock');

    $this->travelTo(Carbon::parse('2026-10-05 08:00:30'));
    $this->actingAs($this->user)->post('/attendance/clock')->assertSessionHas('success'); // out is allowed

    $this->actingAs($this->user)->post('/attendance/clock')->assertSessionHas('warning'); // in again too soon
    expect(TimeLog::query()->count())->toBe(2);
});

it('refuses to clock users without an employee record', function () {
    $this->actingAs(userWithRole(Role::Employee))->post('/attendance/clock')->assertForbidden();
});

it('shows my attendance for a period', function () {
    $this->travelTo(Carbon::parse('2026-10-08 12:00'));
    TimeLog::query()->create(['employee_id' => $this->employee->id, 'logged_at' => '2026-10-05 08:30', 'type' => 'in', 'source' => 'web']);
    TimeLog::query()->create(['employee_id' => $this->employee->id, 'logged_at' => '2026-10-05 17:00', 'type' => 'out', 'source' => 'web']);

    $this->actingAs($this->user)
        ->get('/attendance/mine?from=2026-10-05&to=2026-10-07')
        ->assertOk()
        ->assertSee('Mon, Oct 5')
        ->assertSee('0:30') // late
        ->assertSee('Absent'); // Oct 6 and 7

    expect(AttendanceDay::query()->where('employee_id', $this->employee->id)->whereBetween('date', ['2026-10-05', '2026-10-07'])->count())->toBe(3);
});

it('lets HR and payroll view any DTR but not employees', function () {
    $this->actingAs(userWithRole(Role::Payroll))->get("/attendance/dtr?employee={$this->employee->id}")->assertOk()->assertSee($this->employee->full_name);
    $this->actingAs(userWithRole(Role::Hr))->get('/attendance/dtr')->assertOk();
    $this->actingAs($this->user)->get('/attendance/dtr')->assertForbidden();
});
