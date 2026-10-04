<?php

namespace Database\Seeders;

use App\Shared\Authorization\Permission as PermissionEnum;
use App\Shared\Authorization\Role as RoleEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (PermissionEnum::values() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        foreach (RoleEnum::cases() as $roleEnum) {
            Role::findOrCreate($roleEnum->value, 'web')->syncPermissions(
                array_map(fn (PermissionEnum $p) => $p->value, $roleEnum->permissions())
            );
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
