<?php

use App\Features\Attendance\Enums\AttendanceStatus;
use App\Features\Attendance\Enums\HolidayType;
use App\Features\Attendance\Enums\LeaveStatus;
use App\Features\Attendance\Models\AttendanceDay;
use App\Features\Attendance\Models\Holiday;
use App\Features\Attendance\Models\LeaveRequest;
use App\Features\Attendance\Models\LeaveType;
use App\Features\Attendance\Models\Shift;
use App\Features\Employees\Models\Employee;
use App\Shared\Authorization\Role;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-10-20 12:00'));
    Shift::factory()->default()->create();
    $this->vl = LeaveType::query()->create(['code' => 'VL', 'name' => 'Vacation Leave', 'is_paid' => true, 'days_per_year' => 5]);
    $this->user = userWithRole(Role::Employee);
    $this->employee = Employee::factory()->forUser($this->user)->create();
});

it('counts only working days when requesting leave', function () {
    Holiday::query()->create(['date' => '2026-10-07', 'name' => 'Holiday', 'type' => HolidayType::Regular]);

    // Fri Oct 2 to Tue Oct 6 + Wed holiday: Fri, Mon, Tue = 3 days
    $this->actingAs($this->user)->post('/leaves', [
        'leave_type_id' => $this->vl->id, 'start_date' => '2026-10-02', 'end_date' => '2026-10-07', 'reason' => 'Family trip',
    ])->assertSessionHas('success');

    $leave = LeaveRequest::query()->firstOrFail();
    expect((float) $leave->days)->toBe(3.0)->and($leave->status)->toBe(LeaveStatus::Pending);
});

it('enforces yearly balances and rejects overlaps', function () {
    $this->actingAs($this->user)->post('/leaves', [
        'leave_type_id' => $this->vl->id, 'start_date' => '2026-10-05', 'end_date' => '2026-10-09',
    ])->assertSessionHas('success');

    $this->actingAs($this->user)->post('/leaves', [
        'leave_type_id' => $this->vl->id, 'start_date' => '2026-10-12', 'end_date' => '2026-10-12',
    ])->assertSessionHasErrors('end_date');

    LeaveRequest::query()->update(['status' => LeaveStatus::Rejected]);
    $this->actingAs($this->user)->post('/leaves', [
        'leave_type_id' => $this->vl->id, 'start_date' => '2026-10-05', 'end_date' => '2026-10-05',
    ])->assertSessionHasNoErrors();

    $this->actingAs($this->user)->post('/leaves', [
        'leave_type_id' => $this->vl->id, 'start_date' => '2026-10-05', 'end_date' => '2026-10-06',
    ])->assertSessionHasErrors('start_date');
});

it('rejects requests without working days', function () {
    $this->actingAs($this->user)->post('/leaves', [
        'leave_type_id' => $this->vl->id, 'start_date' => '2026-10-10', 'end_date' => '2026-10-11',
    ])->assertSessionHasErrors('end_date');
});

it('lets HR approve leave and marks the days as on leave', function () {
    $leave = LeaveRequest::query()->create([
        'employee_id' => $this->employee->id, 'leave_type_id' => $this->vl->id,
        'start_date' => '2026-10-05', 'end_date' => '2026-10-06', 'days' => 2, 'status' => LeaveStatus::Pending,
    ]);
    $hr = userWithRole(Role::Hr);

    $this->actingAs($hr)->patch("/leaves/{$leave->id}/review", ['decision' => 'approved', 'review_remarks' => 'Enjoy'])
        ->assertSessionHas('success');

    $leave->refresh();
    expect($leave->status)->toBe(LeaveStatus::Approved)->and($leave->reviewed_by)->toBe($hr->id);

    $statuses = AttendanceDay::query()->where('employee_id', $this->employee->id)->orderBy('date')->pluck('status')->all();
    expect($statuses)->toBe([AttendanceStatus::OnLeave, AttendanceStatus::OnLeave]);

    $this->actingAs($hr)->patch("/leaves/{$leave->id}/review", ['decision' => 'rejected'])->assertSessionHas('error');
});

it('prevents reviewing your own leave', function () {
    $hr = userWithRole(Role::Hr);
    $hrEmployee = Employee::factory()->forUser($hr)->create();
    $leave = LeaveRequest::query()->create([
        'employee_id' => $hrEmployee->id, 'leave_type_id' => $this->vl->id,
        'start_date' => '2026-10-05', 'end_date' => '2026-10-05', 'days' => 1, 'status' => LeaveStatus::Pending,
    ]);

    $this->actingAs($hr)->patch("/leaves/{$leave->id}/review", ['decision' => 'approved'])->assertSessionHas('error');
});

it('lets employees cancel only their own pending requests', function () {
    $mine = LeaveRequest::query()->create([
        'employee_id' => $this->employee->id, 'leave_type_id' => $this->vl->id,
        'start_date' => '2026-10-26', 'end_date' => '2026-10-26', 'days' => 1, 'status' => LeaveStatus::Pending,
    ]);
    $other = LeaveRequest::query()->create([
        'employee_id' => Employee::factory()->create()->id, 'leave_type_id' => $this->vl->id,
        'start_date' => '2026-10-26', 'end_date' => '2026-10-26', 'days' => 1, 'status' => LeaveStatus::Pending,
    ]);

    $this->actingAs($this->user)->patch("/leaves/{$mine->id}/cancel")->assertSessionHas('success');
    $this->actingAs($this->user)->patch("/leaves/{$other->id}/cancel")->assertForbidden();
    expect($mine->fresh()->status)->toBe(LeaveStatus::Cancelled);
});

it('forbids employees from the approval queue', function () {
    $this->actingAs($this->user)->get('/leaves/review')->assertForbidden();
});
