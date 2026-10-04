@extends('layouts.app')

@section('title', $department->exists ? 'Edit department' : 'New department')

@section('page')
    <form method="post" class="card"
        action="{{ $department->exists ? route('departments.update', $department) : route('departments.store') }}">
        @csrf
        @if ($department->exists) @method('put') @endif
        <div class="card-body row g-3">
            <x-form.input name="code" label="Code" :value="$department->code" col="col-md-3" required maxlength="20" />
            <x-form.input name="name" label="Name" :value="$department->name" col="col-md-9" required />
            <x-form.select name="head_employee_id" label="Department head (approves when an employee has no supervisor)" :options="$employees"
                :value="$department->head_employee_id" col="col-12" placeholder="— None —" />
            <x-form.textarea name="description" label="Description" :value="$department->description" />
        </div>
        <div class="card-footer d-flex gap-2">
            <button type="submit" class="btn btn-primary">Save</button>
            <a href="{{ route('departments.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
@stop
