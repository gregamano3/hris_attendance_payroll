<?php

namespace App\Features\Attendance\Compute;

use App\Features\Attendance\Enums\AttendanceStatus;
use Illuminate\Support\Carbon;

/**
 * Pure attendance rules for a single day. No database access, so every rule
 * is unit tested in isolation (tests/Unit/Attendance/AttendanceCalculatorTest).
 *
 * Rules:
 *  - The first "in" and the last "out" of the day's window are used.
 *  - Late: minutes after shift start, only when beyond the grace period
 *    (then the full lateness counts).
 *  - Undertime: minutes left before shift end.
 *  - Worked: scheduled minutes (span minus break) minus late and undertime.
 *  - Overtime: minutes after shift end, ignored below the configured threshold
 *    and capped at the approved minutes when overtime requires approval.
 *  - Rest days and holidays: everything worked counts, no late/undertime; time
 *    beyond the scheduled minutes is overtime.
 *  - Night differential: paid minutes between 22:00 and 06:00.
 *  - Half-day leave: the morning (am) or afternoon (pm) half of the shift is
 *    on leave; the other half follows the normal rules.
 */
class AttendanceCalculator
{
    public function __construct(
        private int $overtimeThresholdMinutes = 30,
        private int $nightStartHour = 22,
        private int $nightEndHour = 6,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            (int) config('hris.attendance.overtime_threshold_minutes', 30),
            (int) config('hris.attendance.night_diff_start_hour', 22),
            (int) config('hris.attendance.night_diff_end_hour', 6),
        );
    }

    public function compute(DayInput $input): DayResult
    {
        $shift = $input->shift;
        $now = $input->now ?? Carbon::now();
        $isRestDay = ! $shift->isWorkDay($input->date);
        $start = $shift->startsAt($input->date);
        $end = $shift->endsAt($input->date);
        $scheduled = $shift->scheduledMinutes();
        $halfDay = $input->halfDayLeave !== null && $input->leaveRequestId !== null && ! $isRestDay;
        $leaveFraction = match (true) {
            $input->leaveRequestId === null || $isRestDay => 0.0,
            $halfDay => 0.5,
            default => 1.0,
        };

        if ($halfDay) {
            $half = intdiv((int) $start->diffInMinutes($end), 2);
            $input->halfDayLeave === 'am' ? $start = $start->copy()->addMinutes($half) : $end = $end->copy()->subMinutes($half);
            $scheduled = intdiv($scheduled, 2);
        }

        $timeIn = $this->earliest($input->timeIns);
        $timeOut = $this->latest(array_values(array_filter(
            $input->timeOuts,
            fn (Carbon $out) => $timeIn === null || $out->gt($timeIn),
        )));

        $base = [
            'isRestDay' => $isRestDay,
            'holiday' => $input->holiday,
            'leaveRequestId' => $input->leaveRequestId,
            'leaveFraction' => $leaveFraction,
        ];

        if ($timeIn === null && $timeOut === null) {
            return new DayResult(...$base, status: match (true) {
                $input->leaveRequestId !== null && ! $isRestDay && ! $halfDay => AttendanceStatus::OnLeave,
                $input->holiday !== null => AttendanceStatus::Holiday,
                $isRestDay => AttendanceStatus::RestDay,
                $now->lt($end) => AttendanceStatus::Upcoming,
                default => AttendanceStatus::Absent,
            });
        }

        if ($timeIn === null || $timeOut === null) {
            return new DayResult(...$base, status: AttendanceStatus::Incomplete, timeIn: $timeIn, timeOut: $timeOut);
        }

        // Worked on a rest day or holiday: no tardiness rules apply.
        if ($isRestDay || $input->holiday !== null) {
            $span = $this->minutesBetween($timeIn, $timeOut);
            $paid = max(0, $span - ($span > $scheduled / 2 ? $shift->break_minutes : 0));
            $worked = min($paid, $scheduled);
            $overtime = $this->capToApproved($this->applyThreshold($paid - $worked), $input);

            return new DayResult(
                ...$base,
                status: AttendanceStatus::Present,
                timeIn: $timeIn,
                timeOut: $timeOut,
                workedMinutes: $worked,
                overtimeMinutes: $overtime,
                nightDiffMinutes: min($this->nightMinutes($timeIn, $timeOut), $worked + $overtime),
            );
        }

        $lateRaw = $timeIn->gt($start) ? $this->minutesBetween($start, $timeIn) : 0;
        $late = $lateRaw > $shift->grace_minutes ? $lateRaw : 0;
        $undertime = $timeOut->lt($end) ? $this->minutesBetween($timeOut, $end) : 0;
        $worked = max(0, $scheduled - $late - $undertime);
        $overtime = $timeOut->gt($end) ? $this->capToApproved($this->applyThreshold($this->minutesBetween($end, $timeOut)), $input) : 0;

        $paidStart = $timeIn->gt($start) ? $timeIn : $start;
        $paidEnd = $overtime > 0 ? $end->copy()->addMinutes($overtime) : ($timeOut->lt($end) ? $timeOut : $end);

        return new DayResult(
            ...$base,
            status: AttendanceStatus::Present,
            timeIn: $timeIn,
            timeOut: $timeOut,
            workedMinutes: $worked,
            lateMinutes: $late,
            undertimeMinutes: $undertime,
            overtimeMinutes: $overtime,
            nightDiffMinutes: min($this->nightMinutes($paidStart, $paidEnd), $worked + $overtime),
        );
    }

    private function capToApproved(int $minutes, DayInput $input): int
    {
        return $input->approvedOvertimeMinutes === null ? $minutes : min($minutes, $input->approvedOvertimeMinutes);
    }

    private function applyThreshold(int $minutes): int
    {
        return $minutes >= $this->overtimeThresholdMinutes ? $minutes : 0;
    }

    /**
     * Minutes of [from, to] falling inside the night differential windows.
     */
    private function nightMinutes(Carbon $from, Carbon $to): int
    {
        if ($to->lte($from)) {
            return 0;
        }

        $minutes = 0;
        $day = $from->copy()->subDay()->startOfDay();

        while ($day->lte($to)) {
            $windowStart = $day->copy()->setTime($this->nightStartHour, 0);
            $windowEnd = $day->copy()->addDay()->setTime($this->nightEndHour, 0);

            $overlapStart = $from->max($windowStart);
            $overlapEnd = $to->min($windowEnd);

            if ($overlapEnd->gt($overlapStart)) {
                $minutes += $this->minutesBetween($overlapStart, $overlapEnd);
            }

            $day->addDay();
        }

        return $minutes;
    }

    private function minutesBetween(Carbon $from, Carbon $to): int
    {
        return (int) floor($from->diffInSeconds($to, true) / 60);
    }

    /**
     * @param  list<Carbon>  $times
     */
    private function earliest(array $times): ?Carbon
    {
        return collect($times)->sort()->first();
    }

    /**
     * @param  list<Carbon>  $times
     */
    private function latest(array $times): ?Carbon
    {
        return collect($times)->sort()->last();
    }
}
