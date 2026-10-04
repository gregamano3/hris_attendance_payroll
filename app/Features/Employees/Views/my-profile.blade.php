@extends('layouts.app')

@section('title', 'My profile')

@section('page')
    @if ($employee)
        @include('employees::employees._details')
        @include('employees::employees._documents', ['canManage' => false])
    @else
        <div class="callout callout-info">
            Your user account is not linked to an employee record yet. Please contact Human Resources.
        </div>
    @endif
@stop
