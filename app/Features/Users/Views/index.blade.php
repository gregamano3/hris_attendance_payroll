@extends('layouts.app')

@section('title', 'Users')

@section('page_actions')
    <a href="{{ route('users.create') }}" class="btn btn-primary">
        <i class="bi bi-person-plus me-1"></i> New user
    </a>
@stop

@section('page')
    <div class="card">
        <div class="card-header">
            <form method="get" class="d-flex gap-2" role="search">
                <input type="search" name="search" value="{{ $search }}" class="form-control"
                    placeholder="Search name or email" aria-label="Search users">
                <button class="btn btn-outline-secondary" type="submit">Search</button>
            </form>
        </div>
        <div class="card-body p-0 table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Last login</th>
                        <th class="actions"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->primaryRole()?->label() ?? '—' }}</td>
                            <td>
                                @if ($user->is_active)
                                    <span class="badge text-bg-success">Active</span>
                                @else
                                    <span class="badge text-bg-secondary">Inactive</span>
                                @endif
                            </td>
                            <td>{{ $user->last_login_at?->diffForHumans() ?? 'Never' }}</td>
                            <td class="actions">
                                <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-body-secondary py-4">No users found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($users->hasPages())
            <div class="card-footer">{{ $users->links() }}</div>
        @endif
    </div>
@stop
