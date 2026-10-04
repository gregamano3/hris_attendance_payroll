@extends('layouts.app')

@section('title', 'Branches')

@section('page_actions')
    <a href="{{ route('branches.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> New branch</a>
@stop

@section('page')
    <div class="card">
        <div class="card-body p-0 table-responsive">
            <table class="table table-striped mb-0 align-middle">
                <thead><tr><th>Code</th><th>Name</th><th>Address</th><th>Clock restrictions</th><th class="text-end">Employees</th><th class="actions"></th></tr></thead>
                <tbody>
                    @forelse ($branches as $branch)
                        <tr>
                            <td><span class="badge text-bg-light border">{{ $branch->code }}</span></td>
                            <td>{{ $branch->name }}</td>
                            <td class="small">{{ $branch->address }}</td>
                            <td class="small">
                                @if ($branch->geofence_radius_m)<span class="badge text-bg-info">Geofence {{ $branch->geofence_radius_m }} m</span>@endif
                                @if ($branch->allowed_ip_ranges)<span class="badge text-bg-info">IP allow-list</span>@endif
                            </td>
                            <td class="text-end">{{ $branch->employees_count }}</td>
                            <td class="actions">
                                <a href="{{ route('branches.edit', $branch) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                <x-delete-button :action="route('branches.destroy', $branch)" confirm="Delete this branch?" />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-body-secondary py-4">No branches yet. Without branches every employee follows the nationwide holiday calendar.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@stop
