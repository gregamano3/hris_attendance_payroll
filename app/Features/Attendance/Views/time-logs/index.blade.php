@extends('layouts.app')

@section('title', 'Time logs')

@section('page_actions')
    <a href="{{ route('attendance.import.create') }}" class="btn btn-outline-primary"><i class="bi bi-upload me-1"></i> Import CSV</a>
@stop

@section('page')
    <div class="row">
        <div class="col-lg-8">
            <form method="get" class="card card-body row g-2 flex-row align-items-end mb-3">
                <div class="col-md-4">
                    <label for="employee" class="form-label small mb-1">Employee</label>
                    <select id="employee" name="employee" class="form-select">
                        <option value="">All employees</option>
                        @foreach ($employees as $id => $label)
                            <option value="{{ $id }}" @selected($employeeId === $id)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                @include('attendance::_period-filter')
                <div class="col-md-2 d-grid"><button class="btn btn-outline-secondary">Filter</button></div>
            </form>

            <div class="card">
                <div class="card-body p-0 table-responsive">
                    <table class="table table-sm table-striped mb-0 align-middle">
                        <thead>
                            <tr><th>Employee</th><th>Date &amp; time</th><th>Type</th><th>Source</th><th>Remarks</th><th class="actions"></th></tr>
                        </thead>
                        <tbody>
                            @forelse ($logs as $log)
                                <tr @class(['text-decoration-line-through text-body-secondary' => $log->trashed()])>
                                    <td>{{ $log->employee->full_name }}</td>
                                    <td class="text-nowrap">{{ $log->logged_at->format('M j, Y g:i A') }}</td>
                                    <td>{{ $log->type->label() }}</td>
                                    <td>{{ $log->source->label() }}@if ($log->creator) <span class="small text-body-secondary">· {{ $log->creator->name }}</span>@endif</td>
                                    <td class="small">{{ $log->remarks }}</td>
                                    <td class="actions">
                                        @unless ($log->trashed())
                                            <x-delete-button :action="route('attendance.logs.destroy', $log)" label="Remove" confirm="Remove this punch?" />
                                        @else
                                            <span class="badge text-bg-secondary">Removed</span>
                                        @endunless
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-body-secondary py-4">No time logs.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($logs->hasPages())
                    <div class="card-footer">{{ $logs->links() }}</div>
                @endif
            </div>
        </div>

        <div class="col-lg-4">
            <form method="post" action="{{ route('attendance.logs.store') }}" class="card">
                @csrf
                <div class="card-header"><h3 class="card-title">Add manual punch</h3></div>
                <div class="card-body row g-3">
                    <x-form.select name="employee_id" label="Employee" :options="$employees" col="col-12" placeholder="Select…" required />
                    <x-form.input name="logged_at" label="Date & time" type="datetime-local" col="col-12" required />
                    <x-form.select name="type" label="Type" :options="$types" col="col-12" required />
                    <x-form.input name="remarks" label="Reason" col="col-12" required placeholder="e.g. Forgot to clock out" />
                </div>
                <div class="card-footer"><button class="btn btn-primary">Add punch</button></div>
            </form>
        </div>
    </div>
@stop
