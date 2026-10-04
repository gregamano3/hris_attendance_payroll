<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

it('shows the forgot password page', function () {
    $this->get('/password/reset')->assertOk();
});

it('sends a reset link', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->post('/password/email', ['email' => $user->email])
        ->assertSessionHas('status');

    Notification::assertSentTo($user, ResetPassword::class);
});

it('does not reveal whether an email exists', function () {
    Notification::fake();

    $this->post('/password/email', ['email' => 'nobody@example.com'])
        ->assertSessionHas('status')
        ->assertSessionHasNoErrors();

    Notification::assertNothingSent();
});

it('resets the password with a valid token', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->post('/password/email', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
        $this->get('/password/reset/'.$notification->token.'?email='.urlencode($user->email))
            ->assertOk()
            ->assertSee($user->email);

        $this->post('/password/reset', [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertRedirect('/login')->assertSessionHas('status');

        return true;
    });

    expect(Hash::check('new-password-123', $user->fresh()->password))->toBeTrue();
});

it('rejects an invalid token', function () {
    $user = User::factory()->create();

    $this->post('/password/reset', [
        'token' => 'invalid',
        'email' => $user->email,
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ])->assertSessionHasErrors('email');
});
