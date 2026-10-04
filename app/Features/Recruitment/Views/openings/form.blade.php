@extends('layouts.app')

@section('title', $opening->exists ? 'Edit opening' : 'New job opening')

@section('page')
    <form method="post" class="card" action="{{ $opening->exists ? route('recruitment.openings.update', $opening) : route('recruitment.openings.store') }}">
        @csrf
        @if ($opening->exists) @method('put') @endif
        <div class="card-body row g-3">
            <x-form.input name="title" label="Title" :value="$opening->title" col="col-md-8" required />
            <x-form.input name="slots" label="Slots" type="number" min="1" :value="$opening->slots" col="col-md-4" required />
            <x-form.select name="department_id" label="Department" :options="$departments" :value="$opening->department_id" col="col-md-4" placeholder="—" />
            <x-form.select name="position_id" label="Position" :options="$positions" :value="$opening->position_id" col="col-md-4" placeholder="—" />
            <x-form.select name="branch_id" label="Branch" :options="$branches" :value="$opening->branch_id" col="col-md-4" placeholder="—" />
            <x-form.select name="employment_type" label="Employment type" :options="$employmentTypes" :value="$opening->employment_type" col="col-md-4" required />
            <x-form.textarea name="description" label="Description" :value="$opening->description" rows="6" />
        </div>
        <div class="card-footer d-flex gap-2"><button class="btn btn-primary">Save</button>
            <a href="{{ route('recruitment.openings.index') }}" class="btn btn-outline-secondary">Cancel</a></div>
    </form>
@stop
