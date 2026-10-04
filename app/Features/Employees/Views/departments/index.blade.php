@extends('layouts.app')

@section('title', 'Departments')

@section('page_actions')
    <a href="{{ route('departments.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> New department</a>
@stop

@section('page')
    <div class="card">
        <div class="card-body p-0 table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead>
                    <tr><th>Code</th><th>Name</th><th class="text-end">Positions</th><th class="text-end">Employees</th><th class="actions"></th></tr>
                </thead>
                <tbody>
                    @forelse ($departments as $department)
                        <tr>
                            <td><span class="badge text-bg-light border">{{ $department->code }}</span></td>
                            <td>{{ $department->name }}</td>
                            <td class="text-end">{{ $department->positions_count }}</td>
                            <td class="text-end">{{ $department->employees_count }}</td>
                            <td class="actions">
                                <a href="{{ route('departments.edit', $department) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                <x-delete-button :action="route('departments.destroy', $department)" confirm="Delete this department?" />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-body-secondary py-4">No departments yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($departments->hasPages())
            <div class="card-footer">{{ $departments->links() }}</div>
        @endif
    </div>
@stop
