<?php

use App\Models\User;
use App\Shared\Authorization\Role;
use App\Shared\TwoFactor\TwoFactor;

function enableTwoFactor(User $user): string
{
    $twoFactor = app(TwoFactor::class);
    $secret = $twoFactor->generateSecret();
    $user->forceFill([
        'two_factor_secret' => $secret,
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => ['aaaaa-bbbbb', 'ccccc-ddddd'],
    ])->save();

    return $secret;
}

it('enables two-factor authentication after confirming a code', function () {
    $user = userWithRole(Role::Employee);

    $this->actingAs($user)->post('/account/two-factor', ['current_password' => 'password'])->assertRedirect('/account');
    $user->refresh();
    expect($user->two_factor_secret)->not->toBeNull()->and($user->hasTwoFactorEnabled())->toBeFalse();

    $this->actingAs($user)->get('/account')->assertSee('two-factor-secret', false)->assertSee('<svg', false);

    $this->actingAs($user)->post('/account/two-factor/confirm', ['code' => '000000'])->assertSessionHasErrors('code');

    $code = app(TwoFactor::class)->currentCode($user->two_factor_secret);
    $this->actingAs($user)->post('/account/two-factor/confirm', ['code' => $code])
        ->assertSessionHas('recovery_codes', fn (array $codes) => count($codes) === 8);

    expect($user->fresh()->hasTwoFactorEnabled())->toBeTrue();
});

it('stores the secret encrypted', function () {
    $user = userWithRole(Role::Employee);
    $secret = enableTwoFactor($user);

    $raw = DB::table('users')->where('id', $user->id)->value('two_factor_secret');
    expect($raw)->not->toBe($secret)->and($raw)->not->toContain($secret);
});

it('asks for a code after the password and signs in with a valid one', function () {
    $user = userWithRole(Role::Employee);
    $secret = enableTwoFactor($user);

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/two-factor-challenge');
    $this->assertGuest();

    $this->post('/two-factor-challenge', ['code' => '111111'])->assertSessionHasErrors('code');
    $this->assertGuest();

    $this->post('/two-factor-challenge', ['code' => app(TwoFactor::class)->currentCode($secret)])->assertRedirect('/dashboard');
    $this->assertAuthenticatedAs($user);
});

it('accepts each recovery code only once', function () {
    $user = userWithRole(Role::Employee);
    enableTwoFactor($user);

    $this->post('/login', ['email' => $user->email, 'password' => 'password']);
    $this->post('/two-factor-challenge', ['recovery_code' => 'AAAAA-BBBBB'])->assertRedirect('/dashboard');
    expect($user->fresh()->two_factor_recovery_codes)->toBe(['ccccc-ddddd']);

    $this->post('/logout');
    $this->post('/login', ['email' => $user->email, 'password' => 'password']);
    $this->post('/two-factor-challenge', ['recovery_code' => 'aaaaa-bbbbb'])->assertSessionHasErrors('recovery_code');
    $this->assertGuest();
});

it('requires a password to log in before the challenge', function () {
    $this->get('/two-factor-challenge')->assertRedirect('/login');
    $this->post('/two-factor-challenge', ['code' => '123456'])->assertRedirect('/login');
});

it('expires the pending challenge', function () {
    $user = userWithRole(Role::Employee);
    $secret = enableTwoFactor($user);

    $this->post('/login', ['email' => $user->email, 'password' => 'password']);
    $this->travel(11)->minutes();

    $this->post('/two-factor-challenge', ['code' => app(TwoFactor::class)->currentCode($secret)])->assertRedirect('/login');
    $this->assertGuest();
});

it('disables two-factor authentication with the current password', function () {
    $user = userWithRole(Role::Employee);
    enableTwoFactor($user);

    $this->actingAs($user)->delete('/account/two-factor', ['current_password' => 'wrong'])->assertSessionHasErrors('current_password');
    $this->actingAs($user)->delete('/account/two-factor', ['current_password' => 'password'])->assertSessionHas('success');

    expect($user->fresh()->hasTwoFactorEnabled())->toBeFalse();
});

it('forces required roles to enable two-factor authentication', function () {
    config(['hris.require_two_factor_roles' => 'admin,payroll']);
    $officer = userWithRole(Role::Payroll);

    $this->actingAs($officer)->get('/dashboard')->assertRedirect('/account');
    $this->actingAs($officer)->get('/account')->assertOk()->assertSee('Your role requires it');
    $this->actingAs(userWithRole(Role::Employee))->get('/dashboard')->assertOk();

    enableTwoFactor($officer);
    $this->actingAs($officer)->get('/dashboard')->assertOk();
    $this->actingAs($officer)->delete('/account/two-factor', ['current_password' => 'password'])->assertSessionHas('error');
});
