@extends('layouts.app')

@section('title', 'My account')

@section('page')
    <div class="row">
        <div class="col-lg-5">
            <div class="card card-primary card-outline">
                <div class="card-body">
                    <h3 class="fs-5 mb-3">{{ $user->name }}</h3>
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Email</dt><dd class="col-sm-8">{{ $user->email }}</dd>
                        <dt class="col-sm-4">Role</dt><dd class="col-sm-8">{{ $user->primaryRole()?->label() ?? '—' }}</dd>
                        <dt class="col-sm-4">Last login</dt><dd class="col-sm-8">{{ $user->last_login_at?->format('M j, Y g:i A') ?? '—' }}</dd>
                    </dl>
                    <p class="small text-body-secondary mt-3 mb-0">Contact an administrator to change your name, email or role.</p>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <form method="post" action="{{ route('account.password') }}" class="card">
                @csrf
                @method('put')
                <div class="card-header"><h3 class="card-title">Change password</h3></div>
                <div class="card-body row g-3">
                    <x-form.input name="current_password" label="Current password" type="password" col="col-12" autocomplete="current-password" required />
                    <x-form.input name="password" label="New password" type="password" col="col-md-6" autocomplete="new-password" required />
                    <x-form.input name="password_confirmation" label="Confirm new password" type="password" col="col-md-6" autocomplete="new-password" required />
                </div>
                <div class="card-footer"><button class="btn btn-primary">Change password</button></div>
            </form>
        </div>
    </div>

    <div class="card" id="two-factor">
        <div class="card-header"><h3 class="card-title">Two-factor authentication</h3></div>
        <div class="card-body">
            @if (session('recovery_codes'))
                <div class="alert alert-warning">
                    <strong>Save these recovery codes</strong> somewhere safe. Each can be used once if you lose your phone. They won't be shown again.
                    <pre class="mb-0 mt-2" id="recovery-codes">{{ implode("\n", session('recovery_codes')) }}</pre>
                </div>
            @endif

            @if ($user->hasTwoFactorEnabled())
                <p><span class="badge text-bg-success">On</span> since {{ $user->two_factor_confirmed_at->format('M j, Y') }} ·
                    {{ count($user->two_factor_recovery_codes ?? []) }} recovery code(s) left.</p>
                <div class="row g-2">
                    <form method="post" action="{{ route('account.two-factor.recovery-codes') }}" class="col-md-6 d-flex gap-2">
                        @csrf
                        <input type="password" name="current_password" class="form-control form-control-sm" placeholder="Current password" aria-label="Current password" required>
                        <button class="btn btn-sm btn-outline-secondary text-nowrap">New recovery codes</button>
                    </form>
                    @unless ($twoFactorRequired)
                        <form method="post" action="{{ route('account.two-factor.disable') }}" class="col-md-6 d-flex gap-2">
                            @csrf @method('delete')
                            <input type="password" name="current_password" class="form-control form-control-sm" placeholder="Current password" aria-label="Current password" required>
                            <button class="btn btn-sm btn-outline-danger text-nowrap">Turn off</button>
                        </form>
                    @endunless
                </div>
            @elseif ($twoFactorPending)
                <div class="row">
                    <div class="col-md-4 text-center">{!! $twoFactorQr !!}</div>
                    <div class="col-md-8">
                        <p>Scan the QR code with Google Authenticator, Microsoft Authenticator, Authy or a similar app, or enter this key:</p>
                        <p><code id="two-factor-secret">{{ $user->two_factor_secret }}</code></p>
                        <form method="post" action="{{ route('account.two-factor.confirm') }}" class="d-flex gap-2">
                            @csrf
                            <input type="text" name="code" inputmode="numeric" autocomplete="one-time-code" class="form-control @error('code') is-invalid @enderror" placeholder="6-digit code" aria-label="Authentication code" required>
                            <button class="btn btn-primary text-nowrap">Confirm</button>
                            @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </form>
                    </div>
                </div>
            @else
                <p>Protect your account with a code from your phone in addition to your password.
                    @if ($twoFactorRequired)<strong>Your role requires it.</strong>@endif</p>
                <form method="post" action="{{ route('account.two-factor.enable') }}" class="d-flex gap-2" style="max-width: 32rem">
                    @csrf
                    <input type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror" placeholder="Current password" aria-label="Current password" required>
                    <button class="btn btn-primary text-nowrap">Turn on</button>
                </form>
            @endif
        </div>
    </div>

    <div class="card" id="api-access">
        <div class="card-body d-flex justify-content-between align-items-center">
            <div><h3 class="fs-6 mb-1">API access</h3><p class="mb-0 small text-body-secondary">Personal access tokens for integrations and mobile apps.</p></div>
            <a href="{{ route('account.api-tokens') }}" class="btn btn-outline-primary">Manage API tokens</a>
        </div>
    </div>
@stop
