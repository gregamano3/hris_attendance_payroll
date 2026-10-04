@extends('layouts.app')

@section('title', 'Edit employee')

@section('page')
    <form method="post" action="{{ route('employees.update', $employee) }}">
        @method('put')
        @include('employees::employees._form')
        <div class="d-flex gap-2 mb-4">
            <button type="submit" class="btn btn-primary">Save changes</button>
            <a href="{{ route('employees.show', $employee) }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
@stop
