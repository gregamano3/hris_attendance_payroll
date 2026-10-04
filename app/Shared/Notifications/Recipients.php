<?php

namespace App\Shared\Notifications;

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

    public static function active(?User $user): ?User
    {
        return $user !== null && $user->is_active ? $user : null;
    }
}
