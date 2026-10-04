<?php

namespace App\Shared\Notifications;

use App\Features\Employees\Models\Employee;
use App\Features\Employees\Queries\EmployeeDirectory;
use App\Models\User;
use App\Shared\Authorization\Permission;
use Illuminate\Support\Collection;

final class Recipients
{
    /**
     * Active users holding a permission (directly or through their role),
     * optionally excluding someone (e.g. the requester).
     *
     * @return Collection<int, User>
     */
    public static function withPermission(Permission $permission, ?int $exceptUserId = null): Collection
    {
        return User::permission($permission->value)
            ->where('is_active', true)
            ->when($exceptUserId, fn ($q, $id) => $q->whereKeyNot($id))
            ->get();
    }

    /**
     * The employee's supervisor (or department head) when they have an active
     * account, otherwise everyone holding the approval permission.
     *
     * @return Collection<int, User>
     */
    public static function approversFor(Employee $employee, Permission $permission, ?int $exceptUserId = null): Collection
    {
        $approver = app(EmployeeDirectory::class)->approverFor($employee);

        return $approver !== null && $approver->id !== $exceptUserId
            ? collect([$approver])
            : self::withPermission($permission, $exceptUserId);
    }

    public static function active(?User $user): ?User
    {
        return $user !== null && $user->is_active ? $user : null;
    }
}
