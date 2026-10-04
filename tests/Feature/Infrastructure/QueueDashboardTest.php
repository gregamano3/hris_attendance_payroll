<?php

use App\Shared\Authorization\Role;
use Illuminate\Support\Facades\Gate;

it('lets only administrators open the Horizon queue dashboard', function () {
    expect(Gate::forUser(userWithRole(Role::Admin))->allows('viewHorizon'))->toBeTrue()
        ->and(Gate::forUser(userWithRole(Role::Hr))->allows('viewHorizon'))->toBeFalse()
        ->and(Gate::allows('viewHorizon'))->toBeFalse();

    $this->actingAs(userWithRole(Role::Payroll))->get('/horizon')->assertForbidden();
});

it('shows the queue monitor link to administrators', function () {
    $this->actingAs(userWithRole(Role::Admin))->get('/dashboard')->assertSee('Queue monitor');
});

it('hides the queue monitor link from other roles', function () {
    $this->actingAs(userWithRole(Role::Hr))->get('/dashboard')->assertDontSee('Queue monitor');
});
