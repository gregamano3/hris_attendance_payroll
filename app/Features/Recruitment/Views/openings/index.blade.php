@extends('layouts.app')

@section('title', 'Job openings')

@section('page_actions')
    <a href="{{ route('recruitment.openings.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> New opening</a>
@stop

@section('page')
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><a class="nav-link @if ($status === 'open') active @endif" href="{{ route('recruitment.openings.index') }}">Open</a></li>
        <li class="nav-item"><a class="nav-link @if ($status === 'closed') active @endif" href="{{ route('recruitment.openings.index', ['status' => 'closed']) }}">Closed</a></li>
    </ul>
    <div class="card">
        <div class="card-body p-0 table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead><tr><th>Position</th><th>Department</th><th>Branch</th><th class="text-end">Slots</th><th class="text-end">In process</th><th class="text-end">Hired</th></tr></thead>
                <tbody>
                    @forelse ($openings as $opening)
                        <tr>
                            <td><a href="{{ route('recruitment.openings.show', $opening) }}">{{ $opening->title }}</a> <span class="small text-body-secondary">{{ $opening->employment_type->label() }}</span></td>
                            <td>{{ $opening->department?->name ?? '—' }}</td>
                            <td>{{ $opening->branch?->name ?? '—' }}</td>
                            <td class="text-end">{{ $opening->slots }}</td>
                            <td class="text-end">{{ $opening->active_count }}</td>
                            <td class="text-end">{{ $opening->hired_count }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-body-secondary py-4">No {{ $status }} openings.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@stop
