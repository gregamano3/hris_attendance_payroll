@extends('layouts.app')

@section('title', 'Positions')

@section('page_actions')
    <a href="{{ route('positions.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> New position</a>
@stop

@section('page')
    <div class="card">
        <div class="card-body p-0 table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead>
                    <tr><th>Title</th><th>Department</th><th class="text-end">Employees</th><th class="actions"></th></tr>
                </thead>
                <tbody>
                    @forelse ($positions as $position)
                        <tr>
                            <td>{{ $position->title }}</td>
                            <td>{{ $position->department->name ?? '—' }}</td>
                            <td class="text-end">{{ $position->employees_count }}</td>
                            <td class="actions">
                                <a href="{{ route('positions.edit', $position) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                <x-delete-button :action="route('positions.destroy', $position)" confirm="Delete this position?" />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-body-secondary py-4">No positions yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($positions->hasPages())
            <div class="card-footer">{{ $positions->links() }}</div>
        @endif
    </div>
@stop
