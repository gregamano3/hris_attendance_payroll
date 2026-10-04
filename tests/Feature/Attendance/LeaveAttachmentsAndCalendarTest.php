<?php

use App\Features\Attendance\Enums\LeaveStatus;
use App\Features\Attendance\Models\LeaveRequest;
use App\Features\Attendance\Models\LeaveType;
use App\Features\Attendance\Models\Shift;
use App\Features\Employees\Models\Employee;
use App\Shared\Authorization\Role;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->travelTo(Carbon::parse('2026-10-20 12:00'));
    Shift::factory()->default()->create();
    $this->sl = LeaveType::query()->create(['code' => 'SL', 'name' => 'Sick Leave', 'is_paid' => true, 'days_per_year' => 15, 'attachment_required_after_days' => 2]);
    $this->user = userWithRole(Role::Employee);
    $this->employee = Employee::factory()->forUser($this->user)->create(['first_name' => 'Juana']);
});

it('requires a supporting document for long sick leaves', function () {
    $this->actingAs($this->user)->post('/leaves', ['leave_type_id' => $this->sl->id, 'start_date' => '2026-10-26', 'end_date' => '2026-10-28'])
        ->assertSessionHasErrors(['attachment' => 'Sick Leave of more than 2 day(s) needs a supporting document (e.g. medical certificate).']);

    $this->actingAs($this->user)->post('/leaves', ['leave_type_id' => $this->sl->id, 'start_date' => '2026-10-26', 'end_date' => '2026-10-27'])
        ->assertSessionHasNoErrors();
});

it('stores the document encrypted and lets the requester and HR download it', function () {
    $this->actingAs($this->user)->post('/leaves', [
        'leave_type_id' => $this->sl->id, 'start_date' => '2026-10-26', 'end_date' => '2026-10-28',
        'attachment' => UploadedFile::fake()->createWithContent('medcert.pdf', '%PDF MEDICAL-DETAILS'),
    ])->assertSessionHas('success');

    $leave = LeaveRequest::query()->sole();
    expect(Storage::disk('local')->get($leave->attachment_path))->not->toContain('MEDICAL-DETAILS');

    expect($this->actingAs($this->user)->get("/leaves/{$leave->id}/attachment")->assertOk()->getContent())->toBe('%PDF MEDICAL-DETAILS');
    $this->actingAs(userWithRole(Role::Hr))->get("/leaves/{$leave->id}/attachment")->assertOk()->assertHeader('content-disposition', 'attachment; filename="medcert.pdf"');

    $stranger = userWithRole(Role::Employee);
    Employee::factory()->forUser($stranger)->create();
    $this->actingAs($stranger)->get("/leaves/{$leave->id}/attachment")->assertForbidden();
});

it('shows approved and pending leaves on the calendar', function () {
    LeaveRequest::query()->create(['employee_id' => $this->employee->id, 'leave_type_id' => $this->sl->id, 'start_date' => '2026-10-26', 'end_date' => '2026-10-27', 'days' => 2, 'status' => LeaveStatus::Approved]);
    LeaveRequest::query()->create(['employee_id' => $this->employee->id, 'leave_type_id' => $this->sl->id, 'start_date' => '2026-10-05', 'end_date' => '2026-10-05', 'days' => 1, 'status' => LeaveStatus::Rejected]);

    $html = $this->actingAs(userWithRole(Role::Hr))->get('/leaves/calendar?month=2026-10')->assertOk()->getContent();

    expect(substr_count($html, 'Juana '.mb_substr($this->employee->last_name, 0, 1).'. · SL'))->toBe(2);
    $this->actingAs($this->user)->get('/leaves/calendar')->assertForbidden();
});
