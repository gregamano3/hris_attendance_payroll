<?php

namespace App\Features\Attendance\Queries;

use App\Features\Attendance\Compute\ComputeAttendanceDay;
use App\Features\Attendance\Enums\AttendanceStatus;
use App\Features\Attendance\Enums\HolidayType;
use App\Features\Attendance\Models\AttendanceDay;
use App\Shared\Period;
use Illuminate\Support\Collection;

/**
 * Read API used by the DTR pages and by Payroll.
 */
class AttendanceSummary
{
    public function __construct(private ComputeAttendanceDay $compute) {}

    /**
     * Attendance days of the period, computing any missing day first.
     *
     * @return Collection<int, AttendanceDay>
     */
    public function days(int $employeeId, Period $period): Collection
    {
        $days = $this->query($employeeId, $period);

        if ($days->count() < $period->days()) {
            $this->compute->handleRange($employeeId, $period->from, $period->to);
            $days = $this->query($employeeId, $period);
        }

        return $days;
    }

    /**
     * @param  Collection<int, AttendanceDay>  $days
     * @return array<string, int|float>
     */
    public function totals(Collection $days): array
    {
        $present = $days->where('status', AttendanceStatus::Present);
        $ot = fn (callable $filter) => (int) $present->filter($filter)->sum('overtime_minutes');
        $worked = fn (callable $filter) => (int) $present->filter($filter)->sum('worked_minutes');

        $isRegularDay = fn (AttendanceDay $d) => ! $d->is_rest_day && $d->holiday_type === null;
        $isRestDay = fn (AttendanceDay $d) => $d->is_rest_day && $d->holiday_type === null;
        $isSpecial = fn (AttendanceDay $d) => $d->holiday_type === HolidayType::SpecialNonWorking;
        $isRegularHoliday = fn (AttendanceDay $d) => $d->holiday_type === HolidayType::Regular;

        return [
            'days_present' => $present->count(),
            'days_absent' => $days->where('status', AttendanceStatus::Absent)->count(),
            'days_incomplete' => $days->where('status', AttendanceStatus::Incomplete)->count(),
            'days_on_leave' => $days->where('status', AttendanceStatus::OnLeave)->count(),
            'paid_leave_days' => $days->where('status', AttendanceStatus::OnLeave)
                ->filter(fn (AttendanceDay $d) => (bool) $d->leaveRequest?->leaveType->is_paid)->count(),
            'unworked_regular_holidays' => $days->where('status', AttendanceStatus::Holiday)
                ->where('holiday_type', HolidayType::Regular)->filter(fn (AttendanceDay $d) => ! $d->is_rest_day)->count(),
            'late_minutes' => (int) $days->sum('late_minutes'),
            'undertime_minutes' => (int) $days->sum('undertime_minutes'),
            'night_diff_minutes' => (int) $days->sum('night_diff_minutes'),
            'worked_minutes' => (int) $days->sum('worked_minutes'),
            'regular_ot_minutes' => $ot($isRegularDay),
            'rest_day_minutes' => $worked($isRestDay),
            'rest_day_ot_minutes' => $ot($isRestDay),
            'special_holiday_minutes' => $worked($isSpecial),
            'special_holiday_ot_minutes' => $ot($isSpecial),
            'regular_holiday_minutes' => $worked($isRegularHoliday),
            'regular_holiday_ot_minutes' => $ot($isRegularHoliday),
        ];
    }

    /**
     * @return Collection<int, AttendanceDay>
     */
    private function query(int $employeeId, Period $period): Collection
    {
        return AttendanceDay::query()
            ->with(['shift', 'leaveRequest.leaveType'])
            ->where('employee_id', $employeeId)
            ->whereBetween('date', [$period->from->toDateString(), $period->to->toDateString()])
            ->orderBy('date')
            ->get();
    }
}
