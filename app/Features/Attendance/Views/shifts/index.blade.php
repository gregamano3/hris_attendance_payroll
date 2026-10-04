@extends('layouts.app')

@section('title', 'Shifts')

@section('page_actions')
    <a href="{{ route('shifts.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> New shift</a>
@stop

@section('page')
    <div class="card">
        <div class="card-body p-0 table-responsive">
            <table class="table table-striped mb-0 align-middle">
                <thead>
                    <tr><th>Name</th><th>Schedule</th><th>Break</th><th>Grace</th><th>Work days</th><th class="text-end">Assignments</th><th class="actions"></th></tr>
                </thead>
                <tbody>
                    @foreach ($shifts as $shift)
                        <tr>
                            <td>{{ $shift->name }} @if ($shift->is_default)<span class="badge text-bg-primary">Default</span>@endif</td>
                            <td>{{ substr($shift->start_time, 0, 5) }}–{{ substr($shift->end_time, 0, 5) }} @if ($shift->crossesMidnight())<i class="bi bi-moon-stars" title="Overnight"></i>@endif</td>
                            <td>{{ $shift->break_minutes }} min</td>
                            <td>{{ $shift->grace_minutes }} min</td>
                            <td>{{ $shift->workDaysLabel() }}</td>
                            <td class="text-end">{{ $shift->assignments_count }}</td>
                            <td class="actions">
                                <a href="{{ route('shifts.edit', $shift) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                <x-delete-button :action="route('shifts.destroy', $shift)" confirm="Delete this shift?" />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-5">
            <form method="post" action="{{ route('shifts.assign') }}" class="card">
                @csrf
                <div class="card-header"><h3 class="card-title">Assign shift</h3></div>
                <div class="card-body row g-3">
                    <x-form.select name="shift_id" label="Shift" :options="$shifts->mapWithKeys(fn ($s) => [$s->id => $s->label()])" col="col-12" required />
                    <div class="col-12">
                        <label for="employee_ids" class="form-label">Employees</label>
                        <select id="employee_ids" name="employee_ids[]" multiple size="8" class="form-select @error('employee_ids') is-invalid @enderror" required>
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->employee_no }} — {{ $employee->full_name }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Hold Ctrl / Cmd to select several.</div>
                        @error('employee_ids') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <x-form.input name="effective_from" label="Effective from" type="date" :value="today()->toDateString()" col="col-12" required />
                </div>
                <div class="card-footer"><button class="btn btn-primary">Assign</button></div>
            </form>
        </div>
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Recent assignments</h3></div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Employee</th><th>Shift</th><th>Effective</th></tr></thead>
                        <tbody>
                            @forelse ($assignments as $assignment)
                                <tr>
                                    <td>{{ $assignment->employee->full_name }}</td>
                                    <td>{{ $assignment->shift->name }}</td>
                                    <td>{{ $assignment->effective_from->format('M j, Y') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-body-secondary py-3">Employees without an assignment follow the default shift.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@stop
