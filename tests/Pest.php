<?php

use App\Models\User;
use App\Shared\Authorization\Role;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class))
    ->in('Feature');

pest()->extend(TestCase::class)
    ->in('Unit');

/**
 * Create a user with the given application role.
 *
 * @param  array<string, mixed>  $attributes
 */
function userWithRole(Role $role, array $attributes = []): User
{
    return User::factory()->withRole($role)->create($attributes);
}
