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
@stop
