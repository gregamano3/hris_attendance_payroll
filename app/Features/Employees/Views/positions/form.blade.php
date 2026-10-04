@extends('layouts.app')

@section('title', $position->exists ? 'Edit position' : 'New position')

@section('page')
    <form method="post" class="card"
        action="{{ $position->exists ? route('positions.update', $position) : route('positions.store') }}">
        @csrf
        @if ($position->exists) @method('put') @endif
        <div class="card-body row g-3">
            <x-form.input name="title" label="Title" :value="$position->title" required />
            <x-form.select name="department_id" label="Department" :options="$departments"
                :value="$position->department_id" placeholder="— None —" />
            <x-form.textarea name="description" label="Description" :value="$position->description" />
        </div>
        <div class="card-footer d-flex gap-2">
            <button type="submit" class="btn btn-primary">Save</button>
            <a href="{{ route('positions.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
@stop
