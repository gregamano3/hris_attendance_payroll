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
