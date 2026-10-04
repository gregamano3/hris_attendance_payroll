@php use App\Features\Attendance\Models\Shift; @endphp
@extends('layouts.app')

@section('title', $shift->exists ? 'Edit shift' : 'New shift')

@section('page')
    <form method="post" class="card" action="{{ $shift->exists ? route('shifts.update', $shift) : route('shifts.store') }}">
        @csrf
        @if ($shift->exists) @method('put') @endif
        <div class="card-body row g-3">
            <x-form.input name="name" label="Name" :value="$shift->name" col="col-md-6" required />
            <div class="col-md-6"></div>
            <x-form.input name="start_time" label="Start" type="time" :value="substr((string) $shift->start_time, 0, 5)" col="col-md-3" required />
            <x-form.input name="end_time" label="End" type="time" :value="substr((string) $shift->end_time, 0, 5)" col="col-md-3" required
                help="An end before the start means the shift ends the next day." />
            <x-form.input name="break_minutes" label="Break (minutes)" type="number" min="0" :value="$shift->break_minutes" col="col-md-3" required />
            <x-form.input name="grace_minutes" label="Grace period (minutes)" type="number" min="0" :value="$shift->grace_minutes" col="col-md-3" required />
            <div class="col-12">
                <span class="form-label d-block">Work days</span>
                @foreach (Shift::WEEKDAYS as $number => $label)
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" name="work_days[]" id="day-{{ $number }}" value="{{ $number }}"
                            @checked(in_array($number, array_map('intval', old('work_days', $shift->work_days ?? []))))>
                        <label class="form-check-label" for="day-{{ $number }}">{{ $label }}</label>
                    </div>
                @endforeach
                @error('work_days') <div class="text-danger small">{{ $message }}</div> @enderror
            </div>
            <div class="col-12">
                <div class="form-check form-switch">
                    <input type="hidden" name="is_flexible" value="0">
                    <input class="form-check-input" type="checkbox" role="switch" id="is_flexible" name="is_flexible" value="1" @checked(old('is_flexible', $shift->is_flexible))>
                    <label class="form-check-label" for="is_flexible">Flexible schedule (core hours + required hours; start/end define the allowed window)</label>
                </div>
            </div>
            <x-form.input name="core_start" label="Core hours from" type="time" :value="substr((string) $shift->core_start, 0, 5)" col="col-md-3" />
            <x-form.input name="core_end" label="Core hours until" type="time" :value="substr((string) $shift->core_end, 0, 5)" col="col-md-3" />
            <x-form.input name="required_hours" label="Required hours" type="number" step="0.5" min="1" :value="$shift->required_minutes ? $shift->required_minutes / 60 : null" col="col-md-3" />
            <div class="col-12">
                <div class="form-check form-switch">
                    <input type="hidden" name="is_default" value="0">
                    <input class="form-check-input" type="checkbox" role="switch" id="is_default" name="is_default" value="1" @checked(old('is_default', $shift->is_default))>
                    <label class="form-check-label" for="is_default">Default shift for employees without an assignment</label>
                </div>
            </div>
        </div>
        <div class="card-footer d-flex gap-2">
            <button type="submit" class="btn btn-primary">Save</button>
            <a href="{{ route('shifts.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
@stop
