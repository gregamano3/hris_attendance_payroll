@extends('layouts.app')

@section('title', 'Edit user')

@section('page')
    <form method="post" action="{{ route('users.update', $user) }}" class="card">
        @method('put')
        <div class="card-body">
            @include('users::_form')
        </div>
        <div class="card-footer d-flex gap-2">
            <button type="submit" class="btn btn-primary">Save changes</button>
            <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>

    @if ($user->oidc_subject)
        <div class="card" id="sso-link">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div><h3 class="fs-6 mb-1">Single sign-on</h3><p class="mb-0 small text-body-secondary">Linked to an identity provider account. Unlink it if the person's SSO identity changed; it is linked again on their next SSO sign-in.</p></div>
                <form method="post" action="{{ route('users.sso.unlink', $user) }}">
                    @csrf @method('delete')
                    <button class="btn btn-outline-danger">Unlink</button>
                </form>
            </div>
        </div>
    @endif
@stop
