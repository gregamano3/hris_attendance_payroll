<?php

use App\Features\Attendance\Enums\LeaveStatus;
use App\Features\Attendance\Models\LeaveRequest;
use App\Features\Attendance\Models\LeaveType;
use App\Features\Attendance\Models\OvertimeRequest;
use App\Features\Attendance\Models\Shift;
use App\Features\Attendance\Notifications\RequestReviewed;
use App\Features\Attendance\Notifications\RequestSubmitted;
use App\Features\Employees\Models\Employee;
use App\Features\Payroll\Enums\PayrollRunStatus;
use App\Features\Payroll\Enums\PayrollRunType;
use App\Features\Payroll\Models\PayrollRun;
use App\Features\Payroll\Notifications\PayslipReleased;
use App\Shared\Authorization\Role;
use Database\Seeders\PayrollSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
    $this->travelTo(Carbon::parse('2026-10-20 12:00'));
    Shift::factory()->default()->create();
    $this->user = userWithRole(Role::Employee);
    $this->employee = Employee::factory()->forUser($this->user)->create(['hired_at' => '2020-01-01']);
    $this->hr = userWithRole(Role::Hr);
    $this->admin = userWithRole(Role::Admin);
    $this->inactiveHr = userWithRole(Role::Hr, ['is_active' => false]);
    $this->vl = LeaveType::query()->create(['code' => 'VL', 'name' => 'Vacation Leave', 'is_paid' => true, 'days_per_year' => 15]);
});

it('notifies active approvers of new leave requests', function () {
    $this->actingAs($this->user)->post('/leaves', ['leave_type_id' => $this->vl->id, 'start_date' => '2026-10-26', 'end_date' => '2026-10-26']);

    Notification::assertSentTo([$this->hr, $this->admin], RequestSubmitted::class);
    Notification::assertNotSentTo([$this->inactiveHr, $this->user], RequestSubmitted::class);
});

it('notifies the employee when a leave request is reviewed', function () {
    $leave = LeaveRequest::query()->create([
        'employee_id' => $this->employee->id, 'leave_type_id' => $this->vl->id, 'start_date' => '2026-10-26',
        'end_date' => '2026-10-26', 'days' => 1, 'status' => LeaveStatus::Pending,
    ]);

    $this->actingAs($this->hr)->patch("/leaves/{$leave->id}/review", ['decision' => 'rejected', 'review_remarks' => 'Peak season']);

    Notification::assertSentTo($this->user, RequestReviewed::class, function (RequestReviewed $n) {
        $mail = $n->toMail($this->user);

        return $mail->subject === 'Your request was rejected' && in_array('Remarks: Peak season', $mail->introLines, true);
    });
});

it('notifies approvers and employees about overtime requests', function () {
    $this->actingAs($this->user)->post('/overtime', ['date' => '2026-10-19', 'hours' => '2', 'reason' => 'Audit']);
    Notification::assertSentTo($this->hr, RequestSubmitted::class, fn (RequestSubmitted $n) => str_contains($n->toMail($this->hr)->subject, 'Overtime request'));

    $overtime = OvertimeRequest::query()->sole();
    $this->actingAs($this->hr)->patch("/overtime/{$overtime->id}/review", ['decision' => 'approved']);
    Notification::assertSentTo($this->user, RequestReviewed::class);
});

it('emails every employee with an account when payroll is finalized', function () {
    $this->seed(PayrollSeeder::class);
    $noAccount = Employee::factory()->create(['hired_at' => '2020-01-01']);
    $officer = userWithRole(Role::Payroll);
    $run = PayrollRun::query()->create([
        'name' => 'Payroll', 'type' => PayrollRunType::Regular, 'period_start' => '2026-10-01',
        'period_end' => '2026-10-15', 'pay_date' => '2026-10-15', 'status' => PayrollRunStatus::Draft,
    ]);

    $this->actingAs($officer)->post("/payroll/runs/{$run->id}/compute");
    Notification::assertNothingSent();

    $this->actingAs($officer)->post("/payroll/runs/{$run->id}/finalize");

    Notification::assertSentTo($this->user, PayslipReleased::class, function (PayslipReleased $n) {
        $mail = $n->toMail($this->user);

        return str_contains($mail->subject, 'Oct 1–15, 2026') && ! str_contains(implode(' ', $mail->introLines), '₱');
    });
    Notification::assertCount(1);
    expect($noAccount->user_id)->toBeNull();
});
