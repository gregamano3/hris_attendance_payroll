@extends('layouts.app')

@section('title', 'Daily time records')

@section('page')
    <form method="get" class="card card-body row g-2 flex-row align-items-end mb-3">
        <div class="col-md-4">
            <label for="employee" class="form-label small mb-1">Employee</label>
            <select id="employee" name="employee" class="form-select" required>
                <option value="">Select an employee…</option>
                @foreach ($employees as $option)
                    <option value="{{ $option->id }}" @selected($employee?->id === $option->id)>{{ $option->employee_no }} — {{ $option->full_name }}</option>
                @endforeach
            </select>
        </div>
        @include('attendance::_period-filter')
        <div class="col-md-2 d-grid"><button class="btn btn-primary">Show</button></div>
    </form>

    @if ($employee)
        <div class="d-flex justify-content-between align-items-baseline mb-3">
            <h2 class="fs-5 mb-0">{{ $employee->full_name }} <small class="text-body-secondary">{{ $employee->employee_no }}</small></h2>
            <span class="text-body-secondary">
                {{ $period->label() }}
                <a href="{{ route('attendance.dtr.pdf', ['employee' => $employee, 'from' => $period->from->toDateString(), 'to' => $period->to->toDateString()]) }}"
                    class="btn btn-sm btn-outline-secondary ms-2"><i class="bi bi-printer me-1"></i> Print DTR</a>
                <a href="{{ route('attendance.dtr.xlsx', ['employee' => $employee, 'from' => $period->from->toDateString(), 'to' => $period->to->toDateString()]) }}"
                    class="btn btn-sm btn-outline-secondary"><i class="bi bi-file-earmark-excel me-1"></i> Excel</a>
            </span>
        </div>
        @include('attendance::_dtr-table')
    @endif
@stop
