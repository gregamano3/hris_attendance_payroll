@extends('layouts.app')

@section('title', 'New employee')

@section('page')
    <form method="post" action="{{ route('employees.store') }}">
        @include('employees::employees._form')
        <div class="d-flex gap-2 mb-4">
            <button type="submit" class="btn btn-primary">Create employee</button>
            <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
@stop
