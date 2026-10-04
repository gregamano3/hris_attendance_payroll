<?php

namespace App\Features\Attendance;

use App\Features\Attendance\Compute\AccrueLeaveCreditsCommand;
use App\Features\Attendance\Compute\ComputeAttendanceCommand;
use App\Features\Attendance\Compute\RecomputeAttendanceJob;
use App\Features\Attendance\Enums\LeaveStatus;
use App\Features\Attendance\Models\EmployeeShift;
use App\Features\Attendance\Models\Holiday;
use App\Features\Attendance\Models\LeaveRequest;
use App\Features\Attendance\Models\OvertimeRequest;
use App\Features\Attendance\Models\TimeLog;
use App\Features\Attendance\Queries\HolidayCalendar;
use App\Features\Employees\Models\Employee;
use App\Features\Employees\Queries\EmployeeDirectory;
use App\Models\User;
use App\Shared\Providers\FeatureServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

class AttendanceServiceProvider extends FeatureServiceProvider
{
    protected function bootFeature(): void
    {
        $this->commands([ComputeAttendanceCommand::class, AccrueLeaveCreditsCommand::class]);

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            // Finalise yesterday (absences) and refresh today shortly after midnight.
            $schedule->command('attendance:compute')->dailyAt('00:30')->withoutOverlapping();
            // Credit the month that just ended.
            $schedule->command('leaves:accrue')->monthlyOn(1, '01:00')->withoutOverlapping();
        });

        $this->registerRecomputeTriggers();

        // Approvers: HR (permission) or supervisors / department heads for their team.
        foreach (['review-leaves' => 'leaves.approve', 'review-overtime' => 'overtime.approve'] as $ability => $permission) {
            Gate::define($ability, fn (User $user) => $user->hasPermissionTo($permission)
                || app(EmployeeDirectory::class)->teamIdsOf($user)->isNotEmpty());
        }
    }

    /**
     * Keep attendance_days in sync with the data they are derived from.
     */
    private function registerRecomputeTriggers(): void
    {
        $onLogChange = function (TimeLog $log) {
            // A punch may belong to the previous day's (overnight) shift.
            RecomputeAttendanceJob::dispatch(
                $log->employee_id,
                $log->logged_at->copy()->subDay()->toDateString(),
                $log->logged_at->toDateString(),
            );
        };
        TimeLog::saved($onLogChange);
        TimeLog::deleted($onLogChange);

        $onShiftChange = function (EmployeeShift $assignment) {
            $from = $assignment->effective_from->copy();

            if ($from->lte(today())) {
                RecomputeAttendanceJob::dispatch(
                    $assignment->employee_id,
                    $from->max(today()->subDays(62))->toDateString(),
                    today()->toDateString(),
                );
            }
        };
        EmployeeShift::saved($onShiftChange);
        EmployeeShift::deleted($onShiftChange);

        $onHolidayChange = function (Holiday $holiday) {
            HolidayCalendar::flush($holiday->date->year);

            if ($holiday->getOriginal('date')) {
                HolidayCalendar::flush(Carbon::parse($holiday->getOriginal('date'))->year);
            }

            if ($holiday->date->lte(today())) {
                Employee::query()->active()->pluck('id')->each(
                    fn (int $id) => RecomputeAttendanceJob::dispatch($id, $holiday->date->toDateString(), $holiday->date->toDateString())
                );
            }
        };
        Holiday::saved($onHolidayChange);
        Holiday::deleted($onHolidayChange);

        LeaveRequest::saved(function (LeaveRequest $leave) {
            $wasApproved = $leave->getOriginal('status') === LeaveStatus::Approved;

            if ($leave->status === LeaveStatus::Approved || $wasApproved) {
                $to = $leave->end_date->copy()->min(today());

                if ($leave->start_date->lte($to)) {
                    RecomputeAttendanceJob::dispatch($leave->employee_id, $leave->start_date->toDateString(), $to->toDateString());
                }
            }
        });

        $onOvertimeChange = function (OvertimeRequest $overtime) {
            if ($overtime->date->lte(today())) {
                RecomputeAttendanceJob::dispatch($overtime->employee_id, $overtime->date->toDateString(), $overtime->date->toDateString());
            }
        };
        OvertimeRequest::saved($onOvertimeChange);
        OvertimeRequest::deleted($onOvertimeChange);
    }
}
