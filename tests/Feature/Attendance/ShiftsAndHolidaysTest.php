<?php

use App\Features\Attendance\Enums\AttendanceStatus;
use App\Features\Attendance\Enums\HolidayType;
use App\Features\Attendance\Models\AttendanceDay;
use App\Features\Attendance\Models\EmployeeShift;
use App\Features\Attendance\Models\Holiday;
use App\Features\Attendance\Models\Shift;
use App\Features\Attendance\Models\TimeLog;
use App\Features\Attendance\Queries\HolidayCalendar;
use App\Features\Employees\Models\Employee;
use App\Shared\Authorization\Role;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-10-10 12:00'));
    $this->hr = userWithRole(Role::Hr);
});

it('creates shifts and keeps a single default', function () {
    $old = Shift::factory()->default()->create();

    $this->actingAs($this->hr)->post('/shifts', [
        'name' => 'Mid shift', 'start_time' => '12:00', 'end_time' => '21:00', 'break_minutes' => 60,
        'grace_minutes' => 10, 'work_days' => ['1', '2', '3', '4', '5', '6'], 'is_default' => '1',
    ])->assertRedirect('/shifts');

    $shift = Shift::query()->where('name', 'Mid shift')->firstOrFail();
    expect($shift->is_default)->toBeTrue()
        ->and($shift->work_days)->toBe([1, 2, 3, 4, 5, 6])
        ->and($old->fresh()->is_default)->toBeFalse();
});

it('assigns shifts and recomputes past days with the new schedule', function () {
    Shift::factory()->default()->create();
    $night = Shift::factory()->night()->create();
    $employee = Employee::factory()->create();
    TimeLog::query()->create(['employee_id' => $employee->id, 'logged_at' => '2026-10-05 22:00', 'type' => 'in', 'source' => 'web']);
    TimeLog::query()->create(['employee_id' => $employee->id, 'logged_at' => '2026-10-06 07:00', 'type' => 'out', 'source' => 'web']);

    $this->actingAs($this->hr)->post('/shifts/assign', [
        'shift_id' => $night->id, 'employee_ids' => [$employee->id], 'effective_from' => '2026-10-01',
    ])->assertSessionHas('success');

    expect(EmployeeShift::query()->count())->toBe(1);

    $day = AttendanceDay::query()->where('employee_id', $employee->id)->whereDate('date', '2026-10-05')->firstOrFail();
    expect($day->shift_id)->toBe($night->id)
        ->and($day->worked_minutes)->toBe(480)
        ->and($day->night_diff_minutes)->toBe(480);
});

it('does not delete assigned shifts', function () {
    $assignment = EmployeeShift::query()->create([
        'employee_id' => Employee::factory()->create()->id,
        'shift_id' => Shift::factory()->create()->id,
        'effective_from' => '2026-10-01',
    ]);

    $this->actingAs($this->hr)->delete("/shifts/{$assignment->shift_id}")->assertSessionHas('error');
});

it('manages holidays, flushes the cached calendar and recomputes the day', function () {
    Shift::factory()->default()->create();
    $employee = Employee::factory()->create();

    expect(app(HolidayCalendar::class)->forYear(2026))->toBe([]);

    $this->actingAs($this->hr)->post('/holidays', [
        'date' => '2026-10-05', 'name' => 'Company Foundation Day', 'type' => HolidayType::SpecialNonWorking->value,
    ])->assertRedirect('/holidays?year=2026');

    expect(app(HolidayCalendar::class)->typeOn(Carbon::parse('2026-10-05')))->toBe(HolidayType::SpecialNonWorking);

    $day = AttendanceDay::query()->where('employee_id', $employee->id)->whereDate('date', '2026-10-05')->firstOrFail();
    expect($day->status)->toBe(AttendanceStatus::Holiday);

    $holiday = Holiday::query()->firstOrFail();
    $this->actingAs($this->hr)->delete("/holidays/{$holiday->id}")->assertSessionHas('success');
    expect($day->fresh()->status)->toBe(AttendanceStatus::Absent);
});

it('rejects duplicate holiday dates', function () {
    Holiday::query()->create(['date' => '2026-12-25', 'name' => 'Christmas Day', 'type' => HolidayType::Regular]);

    $this->actingAs($this->hr)->post('/holidays', ['date' => '2026-12-25', 'name' => 'Dup', 'type' => 'regular'])
        ->assertSessionHasErrors('date');
});
