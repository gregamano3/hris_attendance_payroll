<?php

use App\Models\User;
use App\Shared\Authorization\Role;

it('shows the login page', function () {
    $this->get('/login')->assertOk()->assertSee('Sign in', false);
});

it('redirects guests to the login page', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

it('logs in with valid credentials and records the login time', function () {
    $user = userWithRole(Role::Employee);

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->last_login_at)->not->toBeNull();
});

it('rejects invalid credentials', function () {
    $user = User::factory()->create();

    $this->from('/login')
        ->post('/login', ['email' => $user->email, 'password' => 'wrong-password'])
        ->assertRedirect('/login')
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('rejects inactive users', function () {
    $user = User::factory()->inactive()->create();

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('locks out after too many failed attempts', function () {
    $user = User::factory()->create();

    foreach (range(1, 5) as $attempt) {
        $this->post('/login', ['email' => $user->email, 'password' => 'wrong']);
    }

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('logs out', function () {
    $this->actingAs(User::factory()->create())
        ->post('/logout')
        ->assertRedirect('/login');

    $this->assertGuest();
});

it('has no public registration', function () {
    $this->get('/register')->assertNotFound();
});
