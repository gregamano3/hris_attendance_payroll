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
        <h2 class="fs-5 mb-3">{{ $period->label() }}</h2>
        @include('attendance::_dtr-table')
    @endif
@stop
