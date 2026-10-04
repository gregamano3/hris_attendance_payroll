<?php

namespace App\Features\Employees\Queries;

use App\Features\Employees\Models\CompensationChange;
use App\Features\Employees\Models\Employee;
use App\Shared\Money\Money;
use App\Shared\Period;
use Illuminate\Support\Carbon;

/**
 * Read API for payroll: which salary rate applies on which day.
 */
class CompensationHistory
{
    /**
     * Rate segments in effect during the period, sorted.
     *
     * @return list<array{from: string|null, rate: Money, rate_type: string}>
     */
    public function segments(Employee $employee, Period $period): array
    {
        $changes = CompensationChange::query()
            ->where('employee_id', $employee->id)
            ->whereDate('effective_from', '<=', $period->to)
            ->orderBy('effective_from')
            ->get();

        $before = $changes->filter(fn (CompensationChange $c) => $c->effective_from->lte($period->from))->last();
        $inside = $changes->filter(fn (CompensationChange $c) => $c->effective_from->gt($period->from));

        if ($before === null && $inside->isEmpty()) {
            return [['from' => null, 'rate' => $employee->basic_rate, 'rate_type' => $employee->rate_type->value]];
        }

        $segments = [];

        if ($before !== null) {
            $segments[] = ['from' => null, 'rate' => $before->basic_rate, 'rate_type' => $before->rate_type->value];
        }

        foreach ($inside as $change) {
            $segments[] = ['from' => $change->effective_from->toDateString(), 'rate' => $change->basic_rate, 'rate_type' => $change->rate_type->value];
        }

        if ($before === null) {
            $segments[0]['from'] = null; // hired inside the period
        }

        return $segments;
    }

    /**
     * Apply the latest change effective on $date to the employee's current rate.
     */
    public function syncCurrent(Employee $employee, ?Carbon $date = null): void
    {
        $current = CompensationChange::query()
            ->where('employee_id', $employee->id)
            ->whereDate('effective_from', '<=', $date ?? today())
            ->orderByDesc('effective_from')
            ->first();

        if ($current !== null && (! $current->basic_rate->equals($employee->basic_rate) || $current->rate_type !== $employee->rate_type)) {
            $employee->forceFill(['basic_rate' => $current->basic_rate, 'rate_type' => $current->rate_type])->saveQuietly();
        }
    }
}
