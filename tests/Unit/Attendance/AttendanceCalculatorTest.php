<?php

use App\Features\Attendance\Compute\AttendanceCalculator;
use App\Features\Attendance\Compute\DayInput;
use App\Features\Attendance\Compute\DayResult;
use App\Features\Attendance\Enums\AttendanceStatus;
use App\Features\Attendance\Enums\HolidayType;
use App\Features\Attendance\Models\Shift;
use Illuminate\Support\Carbon;

function dayShift(): Shift
{
    return new Shift([
        'name' => 'Day', 'start_time' => '08:00:00', 'end_time' => '17:00:00',
        'break_minutes' => 60, 'grace_minutes' => 5, 'work_days' => [1, 2, 3, 4, 5],
    ]);
}

function nightShift(): Shift
{
    return new Shift([
        'name' => 'Night', 'start_time' => '22:00:00', 'end_time' => '07:00:00',
        'break_minutes' => 60, 'grace_minutes' => 5, 'work_days' => [1, 2, 3, 4, 5],
    ]);
}

/**
 * @param  list<string>  $ins
 * @param  list<string>  $outs
 */
function computeDay(string $date, Shift $shift, array $ins = [], array $outs = [], array $extra = []): DayResult
{
    $calculator = new AttendanceCalculator(overtimeThresholdMinutes: 30);

    return $calculator->compute(new DayInput(
        date: Carbon::parse($date),
        shift: $shift,
        timeIns: array_map(fn ($t) => Carbon::parse($t), $ins),
        timeOuts: array_map(fn ($t) => Carbon::parse($t), $outs),
        holiday: $extra['holiday'] ?? null,
        leaveRequestId: $extra['leave'] ?? null,
        now: Carbon::parse($extra['now'] ?? '2026-12-31 00:00'),
    ));
}

// 2026-10-05 is a Monday, 2026-10-10 a Saturday.

it('computes a complete on-time day', function () {
    $day = computeDay('2026-10-05', dayShift(), ['2026-10-05 07:52'], ['2026-10-05 17:03']);

    expect($day->status)->toBe(AttendanceStatus::Present)
        ->and($day->workedMinutes)->toBe(480)
        ->and($day->lateMinutes)->toBe(0)
        ->and($day->undertimeMinutes)->toBe(0)
        ->and($day->overtimeMinutes)->toBe(0)
        ->and($day->nightDiffMinutes)->toBe(0);
});

it('ignores lateness within the grace period', function () {
    $day = computeDay('2026-10-05', dayShift(), ['2026-10-05 08:05'], ['2026-10-05 17:00']);

    expect($day->lateMinutes)->toBe(0)->and($day->workedMinutes)->toBe(480);
});

it('counts the full lateness beyond the grace period', function () {
    $day = computeDay('2026-10-05', dayShift(), ['2026-10-05 08:20'], ['2026-10-05 17:00']);

    expect($day->lateMinutes)->toBe(20)->and($day->workedMinutes)->toBe(460);
});

it('computes undertime', function () {
    $day = computeDay('2026-10-05', dayShift(), ['2026-10-05 08:00'], ['2026-10-05 16:15']);

    expect($day->undertimeMinutes)->toBe(45)->and($day->workedMinutes)->toBe(435);
});

it('uses the first in and the last out', function () {
    $day = computeDay(
        '2026-10-05', dayShift(),
        ['2026-10-05 08:30', '2026-10-05 07:58', '2026-10-05 13:00'],
        ['2026-10-05 12:00', '2026-10-05 17:01'],
    );

    expect($day->timeIn->format('H:i'))->toBe('07:58')
        ->and($day->timeOut->format('H:i'))->toBe('17:01')
        ->and($day->lateMinutes)->toBe(0);
});

it('applies the overtime threshold', function () {
    $short = computeDay('2026-10-05', dayShift(), ['2026-10-05 08:00'], ['2026-10-05 17:25']);
    $long = computeDay('2026-10-05', dayShift(), ['2026-10-05 08:00'], ['2026-10-05 19:30']);

    expect($short->overtimeMinutes)->toBe(0)
        ->and($long->overtimeMinutes)->toBe(150);
});

it('counts night differential on overtime past 10 PM', function () {
    $day = computeDay('2026-10-05', dayShift(), ['2026-10-05 08:00'], ['2026-10-05 23:00']);

    expect($day->overtimeMinutes)->toBe(360)->and($day->nightDiffMinutes)->toBe(60);
});

it('handles a night shift crossing midnight', function () {
    $day = computeDay('2026-10-05', nightShift(), ['2026-10-05 21:55'], ['2026-10-06 07:02']);

    expect($day->status)->toBe(AttendanceStatus::Present)
        ->and($day->workedMinutes)->toBe(480)
        ->and($day->lateMinutes)->toBe(0)
        ->and($day->undertimeMinutes)->toBe(0)
        ->and($day->nightDiffMinutes)->toBe(480);
});

it('computes lateness and undertime on a night shift', function () {
    $day = computeDay('2026-10-05', nightShift(), ['2026-10-05 22:30'], ['2026-10-06 06:00']);

    expect($day->lateMinutes)->toBe(30)
        ->and($day->undertimeMinutes)->toBe(60)
        ->and($day->workedMinutes)->toBe(390)
        ->and($day->nightDiffMinutes)->toBe(390);
});

it('marks absences on past work days without logs', function () {
    expect(computeDay('2026-10-05', dayShift())->status)->toBe(AttendanceStatus::Absent);
});

it('marks future work days as upcoming', function () {
    expect(computeDay('2026-10-05', dayShift(), extra: ['now' => '2026-10-05 10:00'])->status)
        ->toBe(AttendanceStatus::Upcoming);
});

it('marks rest days', function () {
    $day = computeDay('2026-10-10', dayShift());

    expect($day->status)->toBe(AttendanceStatus::RestDay)->and($day->isRestDay)->toBeTrue();
});

it('counts work on a rest day without tardiness and with overtime beyond 8 hours', function () {
    $day = computeDay('2026-10-10', dayShift(), ['2026-10-10 09:00'], ['2026-10-10 20:00']);

    expect($day->status)->toBe(AttendanceStatus::Present)
        ->and($day->isRestDay)->toBeTrue()
        ->and($day->lateMinutes)->toBe(0)
        ->and($day->workedMinutes)->toBe(480)
        ->and($day->overtimeMinutes)->toBe(120);
});

it('marks unworked holidays and keeps the holiday type when worked', function () {
    $off = computeDay('2026-10-05', dayShift(), extra: ['holiday' => HolidayType::Regular]);
    $worked = computeDay('2026-10-05', dayShift(), ['2026-10-05 08:00'], ['2026-10-05 17:00'], ['holiday' => HolidayType::Regular]);

    expect($off->status)->toBe(AttendanceStatus::Holiday)
        ->and($worked->status)->toBe(AttendanceStatus::Present)
        ->and($worked->holiday)->toBe(HolidayType::Regular)
        ->and($worked->workedMinutes)->toBe(480);
});

it('marks approved leaves on work days', function () {
    $day = computeDay('2026-10-05', dayShift(), extra: ['leave' => 42]);

    expect($day->status)->toBe(AttendanceStatus::OnLeave)->and($day->leaveRequestId)->toBe(42);
});

it('flags incomplete logs', function () {
    $day = computeDay('2026-10-05', dayShift(), ['2026-10-05 08:00']);

    expect($day->status)->toBe(AttendanceStatus::Incomplete)->and($day->workedMinutes)->toBe(0);
});

it('caps overtime at the approved minutes when approval is required', function () {
    $calculator = new AttendanceCalculator(overtimeThresholdMinutes: 30);
    $input = fn (?int $approved) => new DayInput(
        date: Carbon::parse('2026-10-05'),
        shift: dayShift(),
        timeIns: [Carbon::parse('2026-10-05 08:00')],
        timeOuts: [Carbon::parse('2026-10-06 00:00')],
        now: Carbon::parse('2026-12-31'),
        approvedOvertimeMinutes: $approved,
    );

    $unapproved = $calculator->compute($input(0));
    $partly = $calculator->compute($input(240));
    $free = $calculator->compute($input(null));

    expect($unapproved->overtimeMinutes)->toBe(0)
        ->and($unapproved->nightDiffMinutes)->toBe(0)
        ->and($partly->overtimeMinutes)->toBe(240)   // until 21:00
        ->and($partly->nightDiffMinutes)->toBe(0)
        ->and($free->overtimeMinutes)->toBe(420)
        ->and($free->nightDiffMinutes)->toBe(120);
});

function halfDay(string $part, array $ins = [], array $outs = []): DayResult
{
    return (new AttendanceCalculator(overtimeThresholdMinutes: 30))->compute(new DayInput(
        date: Carbon::parse('2026-10-05'),
        shift: dayShift(), // 08:00–17:00, half = 4h30
        timeIns: array_map(fn ($t) => Carbon::parse($t), $ins),
        timeOuts: array_map(fn ($t) => Carbon::parse($t), $outs),
        leaveRequestId: 7,
        now: Carbon::parse('2026-12-31'),
        halfDayLeave: $part,
    ));
}

it('expects only the afternoon after a morning half-day leave', function () {
    $onTime = halfDay('am', ['2026-10-05 12:30'], ['2026-10-05 17:00']);
    $late = halfDay('am', ['2026-10-05 13:00'], ['2026-10-05 17:00']);

    expect($onTime->status)->toBe(AttendanceStatus::Present)
        ->and($onTime->workedMinutes)->toBe(240)
        ->and($onTime->lateMinutes)->toBe(0)
        ->and($onTime->leaveFraction)->toBe(0.5)
        ->and($late->lateMinutes)->toBe(30)
        ->and($late->workedMinutes)->toBe(210);
});

it('expects only the morning before an afternoon half-day leave', function () {
    $day = halfDay('pm', ['2026-10-05 08:00'], ['2026-10-05 12:30']);

    expect($day->workedMinutes)->toBe(240)->and($day->undertimeMinutes)->toBe(0);
});

it('marks a half-day leave without punches as absent for the other half', function () {
    $day = halfDay('am');

    expect($day->status)->toBe(AttendanceStatus::Absent)->and($day->leaveFraction)->toBe(0.5);
});

it('records a full leave fraction for full-day leaves', function () {
    expect(computeDay('2026-10-05', dayShift(), extra: ['leave' => 9])->leaveFraction)->toBe(1.0);
});

function flexShift(): Shift
{
    // Window 06:00–20:00, core 10:00–15:00, 8 required hours, 60 min break.
    return new Shift([
        'name' => 'Flexi', 'start_time' => '06:00:00', 'end_time' => '20:00:00', 'break_minutes' => 60, 'grace_minutes' => 0,
        'work_days' => [1, 2, 3, 4, 5], 'is_flexible' => true, 'core_start' => '10:00:00', 'core_end' => '15:00:00', 'required_minutes' => 480,
    ]);
}

it('computes flexible schedules against core and required hours', function () {
    $early = computeDay('2026-10-05', flexShift(), ['2026-10-05 07:00'], ['2026-10-05 16:00']);
    $late = computeDay('2026-10-05', flexShift(), ['2026-10-05 10:30'], ['2026-10-05 19:30']);
    $short = computeDay('2026-10-05', flexShift(), ['2026-10-05 09:00'], ['2026-10-05 15:00']);
    $long = computeDay('2026-10-05', flexShift(), ['2026-10-05 07:00'], ['2026-10-05 18:00']);

    expect([$early->workedMinutes, $early->lateMinutes, $early->undertimeMinutes])->toBe([480, 0, 0])
        ->and([$late->workedMinutes, $late->lateMinutes])->toBe([480, 30])          // missed the core start
        ->and([$short->workedMinutes, $short->undertimeMinutes])->toBe([300, 180])  // 6h present − 1h break
        ->and($long->overtimeMinutes)->toBe(120);
});

it('deducts break time beyond the allowance', function () {
    $day = (new AttendanceCalculator(overtimeThresholdMinutes: 30))->compute(new DayInput(
        date: Carbon::parse('2026-10-05'),
        shift: dayShift(),
        timeIns: [Carbon::parse('2026-10-05 08:00')],
        timeOuts: [Carbon::parse('2026-10-05 17:00')],
        now: Carbon::parse('2026-12-31'),
        breakOuts: [Carbon::parse('2026-10-05 12:00'), Carbon::parse('2026-10-05 15:00')],
        breakIns: [Carbon::parse('2026-10-05 13:15'), Carbon::parse('2026-10-05 15:20')],
    ));

    expect($day->overbreakMinutes)->toBe(35)   // 75 + 20 taken, 60 allowed
        ->and($day->workedMinutes)->toBe(445);
});
