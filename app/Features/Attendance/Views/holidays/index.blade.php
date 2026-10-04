@extends('layouts.app')

@section('title', "Holidays {$year}")

@section('page_actions')
    <div class="btn-group">
        <a href="{{ route('holidays.index', ['year' => $year - 1]) }}" class="btn btn-outline-secondary"><i class="bi bi-chevron-left"></i> {{ $year - 1 }}</a>
        <a href="{{ route('holidays.index', ['year' => $year + 1]) }}" class="btn btn-outline-secondary">{{ $year + 1 }} <i class="bi bi-chevron-right"></i></a>
    </div>
@stop

@section('page')
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body p-0 table-responsive">
                    <table class="table table-striped mb-0 align-middle">
                        <thead><tr><th>Date</th><th>Holiday</th><th>Type</th><th class="actions"></th></tr></thead>
                        <tbody>
                            @forelse ($holidays as $holiday)
                                <tr>
                                    <td class="text-nowrap">{{ $holiday->date->format('D, M j') }}</td>
                                    <td>{{ $holiday->name }}</td>
                                    <td><span class="badge text-bg-{{ $holiday->type->badge() }}">{{ $holiday->type->label() }}</span></td>
                                    <td class="actions">
                                        <a href="{{ route('holidays.edit', $holiday) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                        <x-delete-button :action="route('holidays.destroy', $holiday)" confirm="Remove this holiday?" />
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-body-secondary py-4">No holidays for {{ $year }}.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <form method="post" action="{{ route('holidays.store') }}" class="card">
                @csrf
                <div class="card-header"><h3 class="card-title">Add holiday</h3></div>
                <div class="card-body row g-3">
                    <x-form.input name="date" label="Date" type="date" col="col-12" required />
                    <x-form.input name="name" label="Name" col="col-12" required />
                    <x-form.select name="type" label="Type" :options="$types" col="col-12" required />
                </div>
                <div class="card-footer"><button class="btn btn-primary">Add</button></div>
            </form>
        </div>
    </div>
@stop
