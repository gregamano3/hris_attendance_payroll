<?php

use App\Features\Dashboard\ShowDashboard\GetDashboardStats;
use App\Models\User;
use App\Shared\Authorization\Role;
use Illuminate\Support\Facades\Cache;

it('shows the dashboard to every role', function (Role $role) {
    $this->actingAs(userWithRole($role))->get('/dashboard')->assertOk()->assertSee('Active users');
})->with(Role::cases());

it('redirects the root url to the dashboard', function () {
    $this->get('/')->assertRedirect('/dashboard');
});

it('shows the users menu to admins', function () {
    $this->actingAs(userWithRole(Role::Admin))->get('/dashboard')->assertSee(route('users.index'));
});

it('hides the users menu from roles that cannot manage users', function (Role $role) {
    $this->actingAs(userWithRole($role))->get('/dashboard')->assertDontSee(route('users.index'));
})->with([Role::Hr, Role::Payroll, Role::Employee]);

it('caches the stats and refreshes them when users change', function () {
    userWithRole(Role::Admin);

    $first = app(GetDashboardStats::class)->handle();
    expect(Cache::has(GetDashboardStats::CACHE_KEY))->toBeTrue();

    User::factory()->create();
    expect(Cache::has(GetDashboardStats::CACHE_KEY))->toBeFalse();

    expect(app(GetDashboardStats::class)->handle()['active_users'])->toBe($first['active_users'] + 1);
});
