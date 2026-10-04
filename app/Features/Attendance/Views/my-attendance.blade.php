@extends('layouts.app')

@section('title', 'My attendance')

@section('page')
    @if (! $employee)
        <div class="callout callout-info">Your account is not linked to an employee record. Please contact Human Resources.</div>
    @else
        <form method="get" class="card card-body row g-2 flex-row align-items-end mb-3">
            @include('attendance::_period-filter')
            <div class="col-md-2 d-grid"><button class="btn btn-outline-secondary">Show</button></div>
        </form>
        <div class="d-flex justify-content-between align-items-baseline mb-3">
            <h2 class="fs-5 mb-0">{{ $period->label() }}</h2>
            <a href="{{ route('attendance.mine.pdf', ['from' => $period->from->toDateString(), 'to' => $period->to->toDateString()]) }}"
                class="btn btn-sm btn-outline-secondary"><i class="bi bi-printer me-1"></i> Print DTR</a>
        </div>
        @include('attendance::_dtr-table')
    @endif
@stop
