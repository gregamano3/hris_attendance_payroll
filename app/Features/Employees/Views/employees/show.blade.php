@extends('layouts.app')

@section('title', $employee->full_name)

@section('page_actions')
    @can('employees.manage')
        <a href="{{ route('employees.edit', $employee) }}" class="btn btn-primary"><i class="bi bi-pencil me-1"></i> Edit</a>
        <x-delete-button :action="route('employees.archive', $employee)" label="Archive" class="btn-outline-danger btn-md"
            confirm="Archive this employee? Their history is kept." />
    @endcan
@stop

@section('page')
    @include('employees::employees._details', ['showUser' => true])
@stop
