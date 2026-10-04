<?php

use App\Features\Attendance\Enums\LeaveStatus;
use App\Features\Attendance\Models\LeaveRequest;
use App\Features\Attendance\Models\LeaveType;
use App\Features\Attendance\Models\OvertimeRequest;
use App\Features\Attendance\Models\Shift;
use App\Features\Attendance\Notifications\RequestSubmitted;
use App\Features\Employees\Models\Department;
use App\Features\Employees\Models\Employee;
use App\Shared\Authorization\Role;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
    $this->travelTo(Carbon::parse('2026-10-20 12:00'));
    Shift::factory()->default()->create();
    $this->vl = LeaveType::query()->create(['code' => 'VL', 'name' => 'Vacation Leave', 'is_paid' => true, 'days_per_year' => 15]);
    $this->hr = userWithRole(Role::Hr);

    $this->department = Department::factory()->create();
    $this->supervisorUser = userWithRole(Role::Employee);
    $this->supervisor = Employee::factory()->forUser($this->supervisorUser)->create(['department_id' => $this->department->id]);

    $this->user = userWithRole(Role::Employee);
    $this->employee = Employee::factory()->forUser($this->user)->create(['supervisor_id' => $this->supervisor->id, 'department_id' => $this->department->id]);

    $this->outsider = Employee::factory()->create();
});

function pendingLeave(Employee $employee, $vl): LeaveRequest
{
    return LeaveRequest::query()->create([
        'employee_id' => $employee->id, 'leave_type_id' => $vl->id, 'start_date' => '2026-10-26',
        'end_date' => '2026-10-26', 'days' => 1, 'status' => LeaveStatus::Pending,
    ]);
}

it('notifies the direct supervisor instead of HR', function () {
    $this->actingAs($this->user)->post('/leaves', ['leave_type_id' => $this->vl->id, 'start_date' => '2026-10-26', 'end_date' => '2026-10-26']);

    Notification::assertSentTo($this->supervisorUser, RequestSubmitted::class);
    Notification::assertNotSentTo($this->hr, RequestSubmitted::class);
});

it('falls back to the department head, then to HR', function () {
    $headUser = userWithRole(Role::Employee);
    $head = Employee::factory()->forUser($headUser)->create();
    $this->department->update(['head_employee_id' => $head->id]);
    $this->employee->update(['supervisor_id' => null]);

    $this->actingAs($this->user)->post('/overtime', ['date' => '2026-10-19', 'hours' => '2', 'reason' => 'Audit']);
    Notification::assertSentTo($headUser, RequestSubmitted::class);

    $this->department->update(['head_employee_id' => null]);
    $this->actingAs($this->user)->post('/overtime', ['date' => '2026-10-20', 'hours' => '1', 'reason' => 'Audit']);
    Notification::assertSentTo($this->hr, RequestSubmitted::class);
});

it('shows supervisors only their team in the approval queue', function () {
    pendingLeave($this->employee, $this->vl);
    $other = pendingLeave($this->outsider, $this->vl);

    $this->actingAs($this->supervisorUser)->get('/leaves/review')
        ->assertOk()
        ->assertSee($this->employee->full_name)
        ->assertDontSee($this->outsider->full_name);

    $this->actingAs($this->supervisorUser)->patch("/leaves/{$other->id}/review", ['decision' => 'approved'])->assertForbidden();
    $this->actingAs($this->hr)->get('/leaves/review')->assertSee($this->outsider->full_name);
});

it('lets supervisors approve their team requests', function () {
    $leave = pendingLeave($this->employee, $this->vl);
    $overtime = OvertimeRequest::query()->create(['employee_id' => $this->employee->id, 'date' => '2026-10-19', 'minutes' => 60, 'reason' => 'x', 'status' => LeaveStatus::Pending]);

    $this->actingAs($this->supervisorUser)->patch("/leaves/{$leave->id}/review", ['decision' => 'approved'])->assertSessionHas('success');
    $this->actingAs($this->supervisorUser)->patch("/overtime/{$overtime->id}/review", ['decision' => 'approved'])->assertSessionHas('success');

    expect($leave->fresh()->status)->toBe(LeaveStatus::Approved)->and($leave->fresh()->reviewed_by)->toBe($this->supervisorUser->id);
});

it('keeps the approval menus away from employees without a team', function () {
    $this->actingAs($this->user)->get('/leaves/review')->assertForbidden();
    $this->actingAs($this->supervisorUser)->get('/dashboard')->assertSee(route('leaves.review'));
});

it('sets supervisors and department heads on the forms', function () {
    $this->actingAs($this->hr)->put("/departments/{$this->department->id}", [
        'code' => $this->department->code, 'name' => $this->department->name, 'head_employee_id' => $this->supervisor->id,
    ])->assertSessionHasNoErrors();

    expect($this->department->fresh()->head_employee_id)->toBe($this->supervisor->id);
    $this->actingAs($this->hr)->get("/employees/{$this->employee->id}")->assertSee($this->supervisor->full_name);
});
