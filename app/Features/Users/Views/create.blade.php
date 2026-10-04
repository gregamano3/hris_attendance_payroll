@extends('layouts.app')

@section('title', 'New user')

@section('page')
    <form method="post" action="{{ route('users.store') }}" class="card">
        <div class="card-body">
            @include('users::_form')
        </div>
        <div class="card-footer d-flex gap-2">
            <button type="submit" class="btn btn-primary">Create user</button>
            <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
@stop
