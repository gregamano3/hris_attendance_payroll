@extends('layouts.app')

@section('title', 'Dashboard')

@section('page')
    <div class="row">
        <div class="col-lg-3 col-6">
            <x-adminlte-small-box title="{{ number_format($stats['active_users']) }}" text="Active users"
                icon="bi bi-people-fill" theme="primary" />
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            Welcome back, <strong>{{ auth()->user()->name }}</strong>.
        </div>
    </div>
@stop
