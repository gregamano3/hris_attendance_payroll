<?php

use App\Features\Attendance\Enums\HolidayType;
use App\Features\Attendance\Enums\LeaveStatus;
use App\Features\Attendance\Models\AttendanceDay;
use App\Features\Attendance\Models\Holiday;
use App\Features\Attendance\Models\LeaveRequest;
use App\Features\Attendance\Models\LeaveType;
use App\Features\Attendance\Models\Shift;
use App\Features\Attendance\Models\TimeLog;
use App\Features\Attendance\Queries\AttendanceSummary;
use App\Features\Employees\Models\Employee;
use App\Shared\Period;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-04-20 12:00'));
    Shift::factory()->default()->create();
    $this->employee = Employee::factory()->create(['hired_at' => '2020-01-01']);

    // Maundy Thursday and Good Friday, then Araw ng Kagitingan on Thursday Apr 9.
    foreach ([['2026-04-02', 'Maundy Thursday'], ['2026-04-03', 'Good Friday'], ['2026-04-09', 'Araw ng Kagitingan']] as [$date, $name]) {
        Holiday::query()->create(['date' => $date, 'name' => $name, 'type' => HolidayType::Regular]);
    }
});

function punch(Employee $employee, string $date): void
{
    TimeLog::query()->create(['employee_id' => $employee->id, 'logged_at' => "{$date} 08:00", 'type' => 'in', 'source' => 'web']);
    TimeLog::query()->create(['employee_id' => $employee->id, 'logged_at' => "{$date} 17:00", 'type' => 'out', 'source' => 'web']);
}

function eligibleOn(Employee $employee, string $date): ?bool
{
    app(AttendanceSummary::class)->days($employee->id, new Period(Carbon::parse('2026-03-30'), Carbon::parse('2026-04-10')));

    return AttendanceDay::query()->where('employee_id', $employee->id)->whereDate('date', $date)->sole()->holiday_pay_eligible;
}

it('pays consecutive holidays when present on the work day before them', function () {
    punch($this->employee, '2026-04-01');

    expect(eligibleOn($this->employee, '2026-04-02'))->toBeTrue()
        ->and(eligibleOn($this->employee, '2026-04-03'))->toBeTrue();
});

it('does not pay holidays after an absence', function () {
    expect(eligibleOn($this->employee, '2026-04-02'))->toBeFalse()
        ->and(eligibleOn($this->employee, '2026-04-03'))->toBeFalse();
});

it('pays the holiday after a paid leave but not after an unpaid one', function () {
    $paid = LeaveType::query()->create(['code' => 'VL', 'name' => 'Vacation Leave', 'is_paid' => true, 'days_per_year' => 15]);
    $unpaid = LeaveType::query()->create(['code' => 'LWOP', 'name' => 'Leave Without Pay', 'is_paid' => false, 'days_per_year' => 0]);

    LeaveRequest::query()->create(['employee_id' => $this->employee->id, 'leave_type_id' => $paid->id, 'start_date' => '2026-04-01', 'end_date' => '2026-04-01', 'days' => 1, 'status' => LeaveStatus::Approved]);
    LeaveRequest::query()->create(['employee_id' => $this->employee->id, 'leave_type_id' => $unpaid->id, 'start_date' => '2026-04-08', 'end_date' => '2026-04-08', 'days' => 1, 'status' => LeaveStatus::Approved]);

    expect(eligibleOn($this->employee, '2026-04-02'))->toBeTrue()
        ->and(eligibleOn($this->employee, '2026-04-09'))->toBeFalse();
});

it('updates eligibility when the preceding work day changes later', function () {
    expect(eligibleOn($this->employee, '2026-04-09'))->toBeFalse();

    punch($this->employee, '2026-04-08'); // e.g. a late biometric import

    expect(AttendanceDay::query()->where('employee_id', $this->employee->id)->whereDate('date', '2026-04-09')->sole()->holiday_pay_eligible)->toBeTrue();
});

it('pays every holiday when the rule is disabled', function () {
    config(['hris.payroll.holiday_eligibility' => false]);

    expect(eligibleOn($this->employee, '2026-04-02'))->toBeTrue();
});
