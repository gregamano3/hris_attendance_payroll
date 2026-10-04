<?php

use App\Models\User;
use App\Shared\Authorization\Role;

beforeEach(function () {
    $this->admin = userWithRole(Role::Admin);
});

it('lists and searches users', function () {
    userWithRole(Role::Hr, ['name' => 'Maria Clara']);
    userWithRole(Role::Employee, ['name' => 'Juan Dela Cruz']);

    $this->actingAs($this->admin)
        ->get('/users?search=maria')
        ->assertOk()
        ->assertSee('Maria Clara')
        ->assertDontSee('Juan Dela Cruz');
});

it('creates a user with a role', function () {
    $this->actingAs($this->admin)
        ->post('/users', [
            'name' => 'Jose Rizal',
            'email' => 'jose@example.com',
            'password' => 'secret-pass',
            'password_confirmation' => 'secret-pass',
            'role' => Role::Payroll->value,
            'is_active' => '1',
        ])
        ->assertRedirect('/users')
        ->assertSessionHas('success');

    $user = User::query()->where('email', 'jose@example.com')->firstOrFail();
    expect($user->hasRole(Role::Payroll->value))->toBeTrue()
        ->and($user->is_active)->toBeTrue();
});

it('validates new users', function () {
    $this->actingAs($this->admin)
        ->post('/users', ['email' => $this->admin->email, 'role' => 'superhero'])
        ->assertSessionHasErrors(['name', 'email', 'password', 'role']);
});

it('updates a user without changing the password', function () {
    $user = userWithRole(Role::Employee);
    $hash = $user->password;

    $this->actingAs($this->admin)
        ->put("/users/{$user->id}", [
            'name' => 'Renamed',
            'email' => $user->email,
            'role' => Role::Hr->value,
            'is_active' => '0',
        ])
        ->assertRedirect('/users');

    $user->refresh();
    expect($user->name)->toBe('Renamed')
        ->and($user->is_active)->toBeFalse()
        ->and($user->password)->toBe($hash)
        ->and($user->getRoleNames()->all())->toBe([Role::Hr->value]);
});

it('prevents admins from deactivating or demoting themselves', function () {
    $this->actingAs($this->admin)
        ->put("/users/{$this->admin->id}", [
            'name' => $this->admin->name,
            'email' => $this->admin->email,
            'role' => Role::Employee->value,
            'is_active' => '0',
        ])
        ->assertSessionHasErrors(['is_active', 'role']);
});

it('forbids non admins from managing users', function (Role $role) {
    $this->actingAs(userWithRole($role))->get('/users')->assertForbidden();
})->with([Role::Hr, Role::Payroll, Role::Employee]);
