@extends('layouts.app')

@section('title', 'Employees')

@section('page_actions')
    <a href="{{ route('employees.export', request()->query()) }}" class="btn btn-outline-secondary"><i class="bi bi-file-earmark-excel me-1"></i> Excel</a>
    @can('employees.manage')
        <a href="{{ route('employees.import.create') }}" class="btn btn-outline-primary"><i class="bi bi-upload me-1"></i> Import</a>
        <a href="{{ route('employees.create') }}" class="btn btn-primary"><i class="bi bi-person-plus me-1"></i> New employee</a>
    @endcan
@stop

@section('page')
    <div class="card">
        <div class="card-header">
            <form method="get" class="row g-2" role="search">
                <div class="col-md-5">
                    <input type="search" name="search" value="{{ $filters['search'] }}" class="form-control"
                        placeholder="Search name or employee no." aria-label="Search employees">
                </div>
                <div class="col-md-3">
                    <select name="department" class="form-select" aria-label="Department">
                        <option value="">All departments</option>
                        @foreach ($departments as $id => $name)
                            <option value="{{ $id }}" @selected($filters['department'] === $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select" aria-label="Status">
                        <option value="">All statuses</option>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected($filters['status']?->value === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-grid">
                    <button class="btn btn-outline-secondary" type="submit">Filter</button>
                </div>
            </form>
        </div>
        <div class="card-body p-0 table-responsive">
            <table class="table table-hover table-striped mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Employee no.</th>
                        <th>Name</th>
                        <th>Department</th>
                        <th>Position</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th class="actions"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($employees as $employee)
                        <tr>
                            <td>{{ $employee->employee_no }}</td>
                            <td><a href="{{ route('employees.show', $employee) }}">{{ $employee->full_name }}</a></td>
                            <td>{{ $employee->department->name ?? '—' }}</td>
                            <td>{{ $employee->position->title ?? '—' }}</td>
                            <td>{{ $employee->employment_type->label() }}</td>
                            <td><span class="badge text-bg-{{ $employee->status->badge() }}">{{ $employee->status->label() }}</span></td>
                            <td class="actions">
                                @can('employees.manage')
                                    <a href="{{ route('employees.edit', $employee) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-body-secondary py-4">No employees found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($employees->hasPages())
            <div class="card-footer">{{ $employees->links() }}</div>
        @endif
    </div>
@stop
