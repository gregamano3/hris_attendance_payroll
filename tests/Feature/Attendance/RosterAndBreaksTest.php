<?php

use App\Features\Attendance\Enums\AttendanceStatus;
use App\Features\Attendance\Enums\TimeLogType;
use App\Features\Attendance\Models\AttendanceDay;
use App\Features\Attendance\Models\RosterEntry;
use App\Features\Attendance\Models\Shift;
use App\Features\Attendance\Models\TimeLog;
use App\Features\Attendance\Queries\AttendanceSummary;
use App\Features\Employees\Models\Employee;
use App\Shared\Authorization\Role;
use App\Shared\Period;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-10-12 12:00'));
    $this->day = Shift::factory()->default()->create();
    $this->night = Shift::factory()->night()->create();
    $this->hr = userWithRole(Role::Hr);
    $this->employee = Employee::factory()->create();
});

it('overrides the schedule for specific dates through the roster', function () {
    // Saturday Oct 10 becomes a night shift work day, Monday Oct 5 a rest day.
    $this->actingAs($this->hr)->post('/attendance/roster', ['cells' => [$this->employee->id => [
        '2026-10-05' => 'rest', '2026-10-10' => (string) $this->night->id, '2026-10-06' => '',
    ]]])->assertSessionHas('success', 'Roster saved (2 change(s)).');

    app(AttendanceSummary::class)->days($this->employee->id, new Period(Carbon::parse('2026-10-05'), Carbon::parse('2026-10-10')));
    $status = fn (string $date) => AttendanceDay::query()->where('employee_id', $this->employee->id)->whereDate('date', $date)->sole();

    expect($status('2026-10-05')->status)->toBe(AttendanceStatus::RestDay)
        ->and($status('2026-10-10')->status)->toBe(AttendanceStatus::Absent)   // scheduled now, no punches
        ->and($status('2026-10-10')->shift_id)->toBe($this->night->id)
        ->and($status('2026-10-06')->status)->toBe(AttendanceStatus::Absent);

    // Clearing a cell removes the override.
    $this->actingAs($this->hr)->post('/attendance/roster', ['cells' => [$this->employee->id => ['2026-10-05' => '']]]);
    expect(RosterEntry::query()->count())->toBe(1);
});

it('shows the weekly roster grid', function () {
    RosterEntry::query()->create(['employee_id' => $this->employee->id, 'date' => '2026-10-13', 'is_rest_day' => true]);

    $this->actingAs($this->hr)->get('/attendance/roster?week=2026-10-12')
        ->assertOk()
        ->assertSee($this->employee->full_name)
        ->assertSee('Default (08:00–17:00)');

    $this->actingAs(userWithRole(Role::Employee))->get('/attendance/roster')->assertForbidden();
});

it('creates flexible shifts', function () {
    $this->actingAs($this->hr)->post('/shifts', [
        'name' => 'Flexi', 'start_time' => '06:00', 'end_time' => '20:00', 'break_minutes' => 60, 'grace_minutes' => 0,
        'work_days' => ['1', '2', '3', '4', '5'], 'is_flexible' => '1', 'core_start' => '10:00', 'core_end' => '15:00', 'required_hours' => '8',
    ])->assertRedirect('/shifts');

    $shift = Shift::query()->where('name', 'Flexi')->sole();
    expect($shift->is_flexible)->toBeTrue()->and($shift->required_minutes)->toBe(480)->and($shift->scheduledMinutes())->toBe(480);

    $this->actingAs($this->hr)->post('/shifts', [
        'name' => 'Broken', 'start_time' => '06:00', 'end_time' => '20:00', 'break_minutes' => 60, 'grace_minutes' => 0,
        'work_days' => ['1'], 'is_flexible' => '1',
    ])->assertSessionHasErrors(['core_start', 'core_end', 'required_hours']);
});

it('records breaks from the time clock and deducts overbreak', function () {
    $user = userWithRole(Role::Employee);
    $employee = Employee::factory()->forUser($user)->create();
    $punch = function (string $at, ?string $action = null) use ($user) {
        $this->travelTo(Carbon::parse($at));
        $this->actingAs($user)->post('/attendance/clock', array_filter(['action' => $action]))->assertSessionHas('success');
    };

    $this->travelTo(Carbon::parse('2026-10-12 07:59'));
    $this->actingAs($user)->post('/attendance/clock', ['action' => 'break'])->assertSessionHas('error'); // not clocked in

    $punch('2026-10-12 08:00');
    $punch('2026-10-12 12:00', 'break');
    $punch('2026-10-12 13:30');            // main button ends the break
    $punch('2026-10-12 17:00');

    expect(TimeLog::query()->where('employee_id', $employee->id)->orderBy('logged_at')->pluck('type')->all())
        ->toBe([TimeLogType::In, TimeLogType::BreakOut, TimeLogType::BreakIn, TimeLogType::Out]);

    $day = AttendanceDay::query()->where('employee_id', $employee->id)->whereDate('date', '2026-10-12')->sole();
    expect($day->overbreak_minutes)->toBe(30)->and($day->worked_minutes)->toBe(450);
});
