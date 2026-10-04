@extends('adminlte::auth.auth-page', ['authType' => 'login'])

@section('auth_header', 'Two-factor authentication')

@section('auth_body')
    <p class="small">Enter the 6-digit code from your authenticator app.</p>
    <form method="post" action="{{ route('two-factor.challenge.store') }}">
        @csrf
        <div class="input-group mb-3">
            <input type="text" name="code" id="code" inputmode="numeric" autocomplete="one-time-code" autofocus
                class="form-control @error('code') is-invalid @enderror" placeholder="123456" aria-label="Authentication code">
            <div class="input-group-text"><span class="bi bi-shield-lock"></span></div>
            @error('code') <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span> @enderror
        </div>
        <div class="d-grid mb-3"><button class="btn btn-primary">Verify</button></div>
    </form>

    <details @if ($errors->has('recovery_code')) open @endif>
        <summary class="small">Lost your phone? Use a recovery code</summary>
        <form method="post" action="{{ route('two-factor.challenge.store') }}" class="mt-2">
            @csrf
            <div class="input-group mb-2">
                <input type="text" name="recovery_code" id="recovery_code" autocomplete="off"
                    class="form-control @error('recovery_code') is-invalid @enderror" placeholder="xxxxx-xxxxx" aria-label="Recovery code">
                @error('recovery_code') <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span> @enderror
            </div>
            <div class="d-grid"><button class="btn btn-outline-secondary">Use recovery code</button></div>
        </form>
    </details>
@stop

@section('auth_footer')
    <p class="my-0"><a href="{{ route('login') }}">Back to sign in</a></p>
@stop
