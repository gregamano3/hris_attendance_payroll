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
@stop
