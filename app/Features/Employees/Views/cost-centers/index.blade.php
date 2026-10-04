@extends('layouts.app')

@section('title', 'Cost centers')

@section('page')
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body p-0">
                    <table class="table table-striped mb-0 align-middle">
                        <thead><tr><th>Code</th><th>Name</th><th class="text-end">Employees</th><th class="actions"></th></tr></thead>
                        <tbody>
                            @forelse ($costCenters as $costCenter)
                                <tr>
                                    <td><span class="badge text-bg-light border">{{ $costCenter->code }}</span></td>
                                    <td>{{ $costCenter->name }}</td>
                                    <td class="text-end">{{ $costCenter->employees_count }}</td>
                                    <td class="actions"><x-delete-button :action="route('cost-centers.destroy', $costCenter)" confirm="Delete this cost center?" /></td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-body-secondary py-4">No cost centers yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <form method="post" action="{{ route('cost-centers.store') }}" class="card">
                @csrf
                <div class="card-header"><h3 class="card-title">Add cost center</h3></div>
                <div class="card-body row g-3">
                    <x-form.input name="code" label="Code" col="col-12" required />
                    <x-form.input name="name" label="Name" col="col-12" required />
                </div>
                <div class="card-footer"><button class="btn btn-primary">Add</button></div>
            </form>
        </div>
    </div>
@stop
