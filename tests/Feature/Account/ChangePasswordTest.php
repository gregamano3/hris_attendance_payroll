<?php

use App\Shared\Authorization\Role;
use Illuminate\Support\Facades\Hash;

beforeEach(fn () => $this->user = userWithRole(Role::Employee));

it('shows the account page to every user', function () {
    $this->actingAs($this->user)->get('/account')->assertOk()->assertSee($this->user->email)->assertSee('Change password');
});

it('links the account page from the user menu', function () {
    $this->actingAs($this->user)->get('/dashboard')->assertSee(url('account'));
});

it('changes the password', function () {
    $this->actingAs($this->user)->put('/account/password', [
        'current_password' => 'password',
        'password' => 'new-secret-123',
        'password_confirmation' => 'new-secret-123',
    ])->assertRedirect('/account')->assertSessionHas('success');

    expect(Hash::check('new-secret-123', $this->user->fresh()->password))->toBeTrue();
});

it('rejects a wrong current password', function () {
    $this->actingAs($this->user)->put('/account/password', [
        'current_password' => 'nope',
        'password' => 'new-secret-123',
        'password_confirmation' => 'new-secret-123',
    ])->assertSessionHasErrors(['current_password' => 'The current password is incorrect.']);
});

it('requires a confirmed password different from the current one', function () {
    $this->actingAs($this->user)->put('/account/password', [
        'current_password' => 'password',
        'password' => 'password',
        'password_confirmation' => 'other',
    ])->assertSessionHasErrors('password');
});

it('signs out other sessions after a password change', function () {
    // A session that authenticated with the old password hash...
    $this->actingAs($this->user)->withSession(['password_hash_web' => $this->user->getAuthPassword()])->get('/account')->assertOk();
    $oldHash = $this->user->getAuthPassword();

    $this->actingAs($this->user)->put('/account/password', [
        'current_password' => 'password',
        'password' => 'new-secret-123',
        'password_confirmation' => 'new-secret-123',
    ]);

    // ...is rejected by AuthenticateSession once the hash changed.
    $this->flushSession();
    $this->actingAs($this->user->fresh())->withSession(['password_hash_web' => $oldHash])->get('/account')->assertRedirect('/login');
});

it('requires authentication', function () {
    $this->get('/account')->assertRedirect('/login');
});
