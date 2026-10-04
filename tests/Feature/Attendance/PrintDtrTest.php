<?php

use App\Features\Attendance\Models\Shift;
use App\Features\Attendance\Models\TimeLog;
use App\Features\Attendance\Queries\AttendanceSummary;
use App\Features\Employees\Models\Employee;
use App\Shared\Authorization\Role;
use App\Shared\Period;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-10-20 12:00'));
    Shift::factory()->default()->create();
    $this->user = userWithRole(Role::Employee);
    $this->employee = Employee::factory()->forUser($this->user)->create(['employee_no' => 'EMP-00042']);
    TimeLog::query()->create(['employee_id' => $this->employee->id, 'logged_at' => '2026-10-05 08:10', 'type' => 'in', 'source' => 'web']);
    TimeLog::query()->create(['employee_id' => $this->employee->id, 'logged_at' => '2026-10-05 17:00', 'type' => 'out', 'source' => 'web']);
});

it('downloads any employee DTR for HR and payroll', function (Role $role) {
    $this->actingAs(userWithRole($role))
        ->get("/attendance/dtr/{$this->employee->id}/pdf?from=2026-10-01&to=2026-10-15")
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertDownload('dtr-EMP-00042-20261001.pdf');
})->with([Role::Hr, Role::Payroll]);

it('lets employees download only their own DTR', function () {
    $this->actingAs($this->user)->get('/attendance/mine/pdf?from=2026-10-01&to=2026-10-15')
        ->assertOk()->assertDownload('dtr-EMP-00042-20261001.pdf');

    $this->actingAs($this->user)->get("/attendance/dtr/{$this->employee->id}/pdf")->assertForbidden();
});

it('renders the CS Form 48 layout', function () {
    $html = view('attendance::dtr-pdf', [
        'employee' => $this->employee,
        'period' => new Period(Carbon::parse('2026-10-01'), Carbon::parse('2026-10-15')),
        'days' => $days = app(AttendanceSummary::class)->days($this->employee->id, new Period(Carbon::parse('2026-10-01'), Carbon::parse('2026-10-15'))),
        'totals' => app(AttendanceSummary::class)->totals($days),
    ])->render();

    expect($html)->toContain('DAILY TIME RECORD')
        ->toContain('Civil Service Form No. 48')
        ->toContain('8:10')      // A.M. arrival
        ->toContain('5:00')      // P.M. departure
        ->toContain('Absent');
});

it('links the printable DTR from the attendance pages', function () {
    $this->actingAs($this->user)->get('/attendance/mine')->assertSee('Print DTR');
});
