{{-- Package login view, plus the flash status (e.g. after a password reset). --}}
@extends('adminlte::auth.login')

@section('auth_header')
    {{ __('adminlte::adminlte.login_message') }}

    @if (session('status'))
        <div class="alert alert-success small mt-3 mb-0" role="alert">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger small mt-3 mb-0" role="alert">{{ session('error') }}</div>
    @endif
@endsection

@section('auth_footer')
    @if (config('hris.sso.enabled'))
        <div class="text-center mb-3">
            <div class="text-body-secondary small mb-2">or</div>
            <a href="{{ route('sso.redirect') }}" class="btn btn-outline-primary w-100"><i class="bi bi-shield-lock me-1"></i> {{ config('hris.sso.label') }}</a>
        </div>
    @endif
    @parent
@endsection
