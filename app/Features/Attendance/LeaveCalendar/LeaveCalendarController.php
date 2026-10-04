<?php

namespace App\Features\Attendance\LeaveCalendar;

use App\Features\Attendance\Enums\LeaveStatus;
use App\Features\Attendance\Models\LeaveRequest;
use App\Features\Employees\Queries\EmployeeDirectory;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Month view of approved and pending leaves: the whole company for HR, the
 * team for supervisors.
 */
class LeaveCalendarController
{
    public function __invoke(Request $request, EmployeeDirectory $directory): View
    {
        $month = rescue(fn () => Carbon::createFromFormat('Y-m', $request->string('month')->toString())->startOfMonth(), today()->startOfMonth(), false);
        $start = $month->copy()->startOfWeek();
        $end = $month->copy()->endOfMonth()->endOfWeek();

        $leaves = LeaveRequest::query()
            ->with(['employee', 'leaveType'])
            ->whereIn('status', [LeaveStatus::Approved, LeaveStatus::Pending])
            ->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start)
            ->when(! $request->user()?->can('leaves.approve'), fn ($q) => $q->whereIn('employee_id', $directory->teamIdsOf($request->user())))
            ->get();

        $byDate = [];

        foreach ($leaves as $leave) {
            for ($d = $leave->start_date->copy()->max($start); $d->lte($leave->end_date->copy()->min($end)); $d->addDay()) {
                $byDate[$d->toDateString()][] = $leave;
            }
        }

        $weeks = [];

        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $weeks[intdiv((int) $start->diffInDays($d), 7)][] = $d->copy();
        }

        return view('attendance::leaves.calendar', compact('month', 'weeks', 'byDate'));
    }
}
