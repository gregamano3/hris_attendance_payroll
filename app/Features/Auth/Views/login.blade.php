{{-- Package login view, plus the flash status (e.g. after a password reset). --}}
@extends('adminlte::auth.login')

@section('auth_header')
    {{ __('adminlte::adminlte.login_message') }}

    @if (session('status'))
        <div class="alert alert-success small mt-3 mb-0" role="alert">{{ session('status') }}</div>
    @endif
@endsection
