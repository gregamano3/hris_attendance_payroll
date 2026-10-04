@extends('layouts.app')

@section('title', $branch->exists ? 'Edit branch' : 'New branch')

@section('page')
    <form method="post" class="card" action="{{ $branch->exists ? route('branches.update', $branch) : route('branches.store') }}">
        @csrf
        @if ($branch->exists) @method('put') @endif
        <div class="card-body row g-3">
            <x-form.input name="code" label="Code" :value="$branch->code" col="col-md-3" required />
            <x-form.input name="name" label="Name" :value="$branch->name" col="col-md-9" required />
            <x-form.textarea name="address" label="Address" :value="$branch->address" />
            <div class="col-12"><h3 class="fs-6 mt-2 mb-0">Time clock restrictions (optional)</h3></div>
            <x-form.input name="latitude" label="Latitude" type="number" step="0.0000001" :value="$branch->latitude" col="col-md-4" />
            <x-form.input name="longitude" label="Longitude" type="number" step="0.0000001" :value="$branch->longitude" col="col-md-4" />
            <x-form.input name="geofence_radius_m" label="Geofence radius (m)" type="number" min="20" :value="$branch->geofence_radius_m" col="col-md-4" />
            <x-form.input name="allowed_ip_ranges" label="Allowed IP addresses / ranges" :value="$branch->allowed_ip_ranges" col="col-12"
                placeholder="203.0.113.10, 198.51.100.0/24" help="Comma separated. Leave empty to allow clocking in from anywhere." />
        </div>
        <div class="card-footer d-flex gap-2">
            <button class="btn btn-primary">Save</button>
            <a href="{{ route('branches.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
@stop
