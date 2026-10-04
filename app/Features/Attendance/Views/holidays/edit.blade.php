@extends('layouts.app')

@section('title', 'Edit holiday')

@section('page')
    <form method="post" action="{{ route('holidays.update', $holiday) }}" class="card">
        @csrf
        @method('put')
        <div class="card-body row g-3">
            <x-form.input name="date" label="Date" type="date" :value="$holiday->date->toDateString()" col="col-md-4" required />
            <x-form.input name="name" label="Name" :value="$holiday->name" col="col-md-4" required />
            <x-form.select name="type" label="Type" :options="$types" :value="$holiday->type" col="col-md-4" required />
            <x-form.select name="branch_id" label="Applies to" :options="$branches" :value="$holiday->branch_id" col="col-md-4" placeholder="Nationwide" />
        </div>
        <div class="card-footer d-flex gap-2">
            <button class="btn btn-primary">Save</button>
            <a href="{{ route('holidays.index', ['year' => $holiday->date->year]) }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
@stop
