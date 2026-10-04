<?php

namespace App\Features\Analytics\ShowAnalytics;

use App\Features\Attendance\Enums\AttendanceStatus;
use App\Features\Attendance\Models\AttendanceDay;
use App\Features\Employees\Models\Employee;
use App\Features\Payroll\Enums\PayrollRunStatus;
use App\Features\Payroll\Models\PayrollRun;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Aggregated HR metrics, cached for 10 minutes.
 */
class HrAnalytics
{
    public const CACHE_KEY = 'analytics:hr';

    /**
     * @return array<string, mixed>
     */
    public function snapshot(?Carbon $today = null): array
    {
        $today ??= today();

        return Cache::remember(self::CACHE_KEY.':'.$today->toDateString(), 600, fn () => [
            'headcount' => Employee::query()->active()->count(),
            'by_department' => $this->countBy('department_id', 'departments'),
            'by_branch' => $this->countBy('branch_id', 'branches'),
            'by_type' => Employee::query()->active()->selectRaw('employment_type as label, count(*) as total')->groupBy('employment_type')->pluck('total', 'label')->all(),
            'movement' => $this->movement($today),
            'attendance' => $this->attendance($today),
            'payroll_cost' => $this->payrollCost($today),
        ]);
    }

    /**
     * @return array<string, int>
     */
    private function countBy(string $column, string $table): array
    {
        return Employee::query()->active()
            ->leftJoin($table, "{$table}.id", '=', "employees.{$column}")
            ->selectRaw("coalesce({$table}.name, 'Unassigned') as label, count(*) as total")
            ->groupBy('label')->orderByDesc('total')
            ->pluck('total', 'label')->map(fn ($v) => (int) $v)->all();
    }

    /**
     * Hires, separations and monthly turnover rate for the last 12 months.
     *
     * @return list<array{month: string, hires: int, separations: int, headcount: int, turnover: float}>
     */
    private function movement(Carbon $today): array
    {
        $rows = [];

        for ($i = 11; $i >= 0; $i--) {
            $start = $today->copy()->startOfMonth()->subMonths($i);
            $end = $start->copy()->endOfMonth();

            $hires = Employee::withTrashed()->whereBetween('hired_at', [$start->toDateString(), $end->toDateString()])->count();
            $separations = Employee::withTrashed()->whereBetween('separated_at', [$start->toDateString(), $end->toDateString()])->count();
            $headcount = Employee::withTrashed()->whereDate('hired_at', '<=', $end)
                ->where(fn ($q) => $q->whereNull('separated_at')->orWhereDate('separated_at', '>', $end))
                ->whereNull('anonymized_at')->count();

            $rows[] = [
                'month' => $start->format('M Y'),
                'hires' => $hires,
                'separations' => $separations,
                'headcount' => $headcount,
                'turnover' => $headcount > 0 ? round($separations / $headcount * 100, 2) : 0.0,
            ];
        }

        return $rows;
    }

    /**
     * Attendance and punctuality over the last 30 days.
     *
     * @return array{attendance_rate: float, late_rate: float, absences: int, days: int}
     */
    private function attendance(Carbon $today): array
    {
        $days = AttendanceDay::query()
            ->whereBetween('date', [$today->copy()->subDays(30)->toDateString(), $today->copy()->subDay()->toDateString()])
            ->whereIn('status', [AttendanceStatus::Present, AttendanceStatus::Absent, AttendanceStatus::Incomplete])
            ->selectRaw("count(*) as scheduled, sum(case when status = 'present' then 1 else 0 end) as present, sum(case when status = 'absent' then 1 else 0 end) as absent, sum(case when late_minutes > 0 then 1 else 0 end) as late")
            ->toBase()
            ->first();

        $scheduled = (int) ($days->scheduled ?? 0);
        $present = (int) ($days->present ?? 0);

        return [
            'attendance_rate' => $scheduled > 0 ? round($present / $scheduled * 100, 1) : 0.0,
            'late_rate' => $present > 0 ? round((int) ($days->late ?? 0) / $present * 100, 1) : 0.0,
            'absences' => (int) ($days->absent ?? 0),
            'days' => $scheduled,
        ];
    }

    /**
     * Gross pay plus employer contributions of finalized payroll per month.
     *
     * @return list<array{month: string, gross: float, employer: float}>
     */
    private function payrollCost(Carbon $today): array
    {
        $runs = PayrollRun::query()->where('status', PayrollRunStatus::Finalized)
            ->whereDate('period_end', '>=', $today->copy()->startOfMonth()->subMonths(11))
            ->get(['period_end', 'total_gross', 'total_employer']);

        $rows = [];

        for ($i = 11; $i >= 0; $i--) {
            $month = $today->copy()->startOfMonth()->subMonths($i);
            $inMonth = $runs->filter(fn (PayrollRun $r) => $r->period_end->isSameMonth($month));
            $rows[] = [
                'month' => $month->format('M Y'),
                'gross' => round($inMonth->sum(fn (PayrollRun $r) => $r->total_gross->centavos) / 100, 2),
                'employer' => round($inMonth->sum(fn (PayrollRun $r) => $r->total_employer->centavos) / 100, 2),
            ];
        }

        return $rows;
    }
}
