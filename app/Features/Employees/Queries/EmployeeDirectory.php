<?php

namespace App\Features\Employees\Queries;

use App\Features\Employees\Models\Department;
use App\Features\Employees\Models\Employee;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Read access to employee records for other features (dashboard, attendance,
 * payroll) without coupling them to the Employees slices.
 */
class EmployeeDirectory
{
    public function activeCount(): int
    {
        return Employee::query()->active()->count();
    }

    public function forUser(User $user): ?Employee
    {
        return Employee::query()->where('user_id', $user->id)->first();
    }

    /**
     * Employees whose requests the user reviews as supervisor or department head.
     *
     * @return Collection<int, int>
     */
    public function teamIdsOf(User $user): Collection
    {
        $me = $this->forUser($user);

        if ($me === null) {
            return collect();
        }

        return Employee::query()
            ->where(fn ($q) => $q->where('supervisor_id', $me->id)
                ->orWhereIn('department_id', Department::query()->where('head_employee_id', $me->id)->select('id')))
            ->whereKeyNot($me->id)
            ->pluck('id');
    }

    /**
     * First-level approver: the direct supervisor, else the department head
     * (when they have an active account). Null means HR approvers.
     */
    public function approverFor(Employee $employee): ?User
    {
        $candidates = [
            $employee->supervisor_id ? Employee::query()->with('user')->find($employee->supervisor_id) : null,
            $employee->department_id ? Department::query()->with('head.user')->find($employee->department_id)?->head : null,
        ];

        foreach ($candidates as $candidate) {
            if ($candidate !== null && ! $candidate->is($employee) && $candidate->user?->is_active) {
                return $candidate->user;
            }
        }

        return null;
    }
}
