<?php

use App\Features\Attendance\Enums\LeaveStatus;
use App\Features\Attendance\Models\AttendanceDay;
use App\Features\Attendance\Models\OvertimeRequest;
use App\Features\Attendance\Models\Shift;
use App\Features\Attendance\Models\TimeLog;
use App\Features\Employees\Models\Employee;
use App\Shared\Authorization\Role;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-10-10 12:00'));
    Shift::factory()->default()->create();
    $this->user = userWithRole(Role::Employee);
    $this->employee = Employee::factory()->forUser($this->user)->create();
    $this->hr = userWithRole(Role::Hr);

    // Worked until 20:00 on Monday Oct 5 (3h after shift end).
    TimeLog::query()->create(['employee_id' => $this->employee->id, 'logged_at' => '2026-10-05 08:00', 'type' => 'in', 'source' => 'web']);
    TimeLog::query()->create(['employee_id' => $this->employee->id, 'logged_at' => '2026-10-05 20:00', 'type' => 'out', 'source' => 'web']);
});

function overtimeOn(string $date): int
{
    return AttendanceDay::query()->whereDate('date', $date)->firstOrFail()->overtime_minutes;
}

it('does not pay overtime without an approved request', function () {
    expect(overtimeOn('2026-10-05'))->toBe(0);
});

it('pays overtime only after approval, capped at the approved hours', function () {
    $this->actingAs($this->user)->post('/overtime', ['date' => '2026-10-05', 'hours' => '2', 'reason' => 'Inventory count'])
        ->assertSessionHas('success');

    $request = OvertimeRequest::query()->firstOrFail();
    expect($request->minutes)->toBe(120)->and(overtimeOn('2026-10-05'))->toBe(0);

    $this->actingAs($this->hr)->patch("/overtime/{$request->id}/review", ['decision' => 'approved'])->assertSessionHas('success');

    expect($request->fresh()->status)->toBe(LeaveStatus::Approved)
        ->and($request->fresh()->reviewed_by)->toBe($this->hr->id)
        ->and(overtimeOn('2026-10-05'))->toBe(120);
});

it('pays the actual overtime when it is shorter than approved', function () {
    OvertimeRequest::query()->create([
        'employee_id' => $this->employee->id, 'date' => '2026-10-05', 'minutes' => 300, 'reason' => 'x', 'status' => LeaveStatus::Approved,
    ]);

    expect(overtimeOn('2026-10-05'))->toBe(180);
});

it('pays all overtime when approval is not required', function () {
    config(['hris.attendance.overtime_requires_approval' => false]);
    TimeLog::query()->first()->touch(); // trigger a recompute

    expect(overtimeOn('2026-10-05'))->toBe(180);
});

it('does not pay rejected overtime', function () {
    $request = OvertimeRequest::query()->create([
        'employee_id' => $this->employee->id, 'date' => '2026-10-05', 'minutes' => 120, 'reason' => 'x', 'status' => LeaveStatus::Pending,
    ]);

    $this->actingAs($this->hr)->patch("/overtime/{$request->id}/review", ['decision' => 'rejected', 'review_remarks' => 'Not authorised'])
        ->assertSessionHas('success');

    expect(overtimeOn('2026-10-05'))->toBe(0);
});

it('validates requests and blocks duplicate pending ones', function () {
    $this->actingAs($this->user)->post('/overtime', ['date' => '2026-12-31', 'hours' => '0.25'])
        ->assertSessionHasErrors(['date', 'hours', 'reason']);

    $this->actingAs($this->user)->post('/overtime', ['date' => '2026-10-05', 'hours' => '1', 'reason' => 'a']);
    $this->actingAs($this->user)->post('/overtime', ['date' => '2026-10-05', 'hours' => '1', 'reason' => 'b'])
        ->assertSessionHasErrors('date');
});

it('lets employees cancel only their own pending requests', function () {
    $mine = OvertimeRequest::query()->create(['employee_id' => $this->employee->id, 'date' => '2026-10-05', 'minutes' => 60, 'reason' => 'x', 'status' => LeaveStatus::Pending]);
    $other = OvertimeRequest::query()->create(['employee_id' => Employee::factory()->create()->id, 'date' => '2026-10-05', 'minutes' => 60, 'reason' => 'x', 'status' => LeaveStatus::Pending]);

    $this->actingAs($this->user)->patch("/overtime/{$mine->id}/cancel")->assertSessionHas('success');
    $this->actingAs($this->user)->patch("/overtime/{$other->id}/cancel")->assertForbidden();
});

it('prevents self-approval and restricts the approval queue', function () {
    $hrEmployee = Employee::factory()->forUser($this->hr)->create();
    $own = OvertimeRequest::query()->create(['employee_id' => $hrEmployee->id, 'date' => '2026-10-05', 'minutes' => 60, 'reason' => 'x', 'status' => LeaveStatus::Pending]);

    $this->actingAs($this->hr)->patch("/overtime/{$own->id}/review", ['decision' => 'approved'])->assertSessionHas('error');
    $this->actingAs($this->hr)->get('/overtime/review')->assertOk();
    $this->actingAs($this->user)->get('/overtime/review')->assertForbidden();
    $this->actingAs(userWithRole(Role::Payroll))->get('/overtime')->assertOk();
});
