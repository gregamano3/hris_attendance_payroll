@extends('layouts.app')

@section('title', 'Roster')

@section('page')
    <form method="get" class="card card-body row g-2 flex-row align-items-end mb-3">
        <div class="col-md-3">
            <label class="form-label small mb-1" for="week">Week of</label>
            <input type="date" id="week" name="week" value="{{ $weekStart->toDateString() }}" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="form-label small mb-1" for="department">Department</label>
            <select id="department" name="department" class="form-select">
                <option value="">All</option>
                @foreach ($departments as $id => $name)
                    <option value="{{ $id }}" @selected($departmentId === $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2 d-grid"><button class="btn btn-outline-secondary">Show</button></div>
        <div class="col-md-4 text-end">
            <a class="btn btn-sm btn-outline-secondary" href="{{ route('roster.index', ['week' => $weekStart->copy()->subWeek()->toDateString(), 'department' => $departmentId]) }}">‹ Previous</a>
            <a class="btn btn-sm btn-outline-secondary" href="{{ route('roster.index', ['week' => $weekStart->copy()->addWeek()->toDateString(), 'department' => $departmentId]) }}">Next ›</a>
        </div>
    </form>

    <form method="post" action="{{ route('roster.store') }}" class="card">
        @csrf
        <div class="card-body p-0 table-responsive">
            <table class="table table-sm table-bordered mb-0 align-middle" id="roster-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        @foreach ($days as $day)
                            <th class="text-center text-nowrap">{{ $day->format('D') }}<br><span class="small text-body-secondary">{{ $day->format('M j') }}</span></th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($employees as $employee)
                        <tr>
                            <td class="text-nowrap">{{ $employee->full_name }}</td>
                            @foreach ($days as $day)
                                @php
                                    $entry = $entries->get($employee->id.'|'.$day->toDateString());
                                    $value = $entry ? ($entry->is_rest_day ? 'rest' : (string) $entry->shift_id) : '';
                                    $default = $defaults[$employee->id][$day->toDateString()];
                                @endphp
                                <td @class(['table-warning' => $entry])>
                                    <select name="cells[{{ $employee->id }}][{{ $day->toDateString() }}]" class="form-select form-select-sm" aria-label="{{ $employee->full_name }} {{ $day->format('D M j') }}">
                                        <option value="">Default{{ $default ? " ({$default})" : '' }}</option>
                                        <option value="rest" @selected($value === 'rest')>Rest day</option>
                                        @foreach ($shifts as $shift)
                                            <option value="{{ $shift->id }}" @selected($value === (string) $shift->id)>{{ $shift->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-body-secondary py-4">No employees.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center">
            <span class="small text-body-secondary">Highlighted cells override the assigned shift for that date.</span>
            <button class="btn btn-primary">Save roster</button>
        </div>
    </form>
@stop
