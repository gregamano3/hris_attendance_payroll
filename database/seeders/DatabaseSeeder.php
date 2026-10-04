<?php

namespace Database\Seeders;

use App\Models\User;
use App\Shared\Authorization\Role;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        $admin = User::query()->firstOrCreate(
            ['email' => config('hris.admin.email')],
            [
                'name' => 'System Administrator',
                'password' => config('hris.admin.password'),
                'email_verified_at' => now(),
            ],
        );

        $admin->syncRoles([Role::Admin->value]);
    }
}
