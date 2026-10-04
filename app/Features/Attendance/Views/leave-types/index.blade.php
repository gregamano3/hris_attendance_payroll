@extends('layouts.app')

@section('title', 'Leave types')

@section('page')
    <div class="callout callout-info small">
        Accruing types earn credits every month (e.g. 1.25 days = 15 a year) via <code>php artisan leaves:accrue</code>, scheduled on the 1st of each month.
        Unused credits up to the carry-over cap move to the next year. Non-accruing types grant <em>days per year</em> on January 1 (0 = no cap).
        Convertible leave is paid out in the final pay.
    </div>
    <div class="card">
        <div class="card-body p-0 table-responsive">
            <table class="table table-sm mb-0 align-middle">
                <thead><tr><th>Code</th><th>Name</th><th>Days / year</th><th>Accrual / month</th><th>Carry-over cap</th><th>Document after (days)</th><th>Paid</th><th>Convertible</th><th></th></tr></thead>
                <tbody>
                    @foreach ($types as $type)
                        <tr>
                            <form method="post" action="{{ route('leave-types.update', $type) }}">
                                @csrf @method('put')
                                <td><input name="code" value="{{ $type->code }}" class="form-control form-control-sm" style="width:5rem" aria-label="Code"></td>
                                <td><input name="name" value="{{ $type->name }}" class="form-control form-control-sm" aria-label="Name"></td>
                                <td><input type="number" name="days_per_year" value="{{ $type->days_per_year }}" min="0" class="form-control form-control-sm" style="width:6rem" aria-label="Days per year"></td>
                                <td><input type="number" step="0.01" name="accrual_per_month" value="{{ (float) $type->accrual_per_month }}" min="0" class="form-control form-control-sm" style="width:6rem" aria-label="Accrual per month"></td>
                                <td><input type="number" name="carry_over_cap" value="{{ $type->carry_over_cap }}" min="0" class="form-control form-control-sm" style="width:6rem" aria-label="Carry-over cap"></td>
                                <td><input type="number" name="attachment_required_after_days" value="{{ $type->attachment_required_after_days }}" min="0" class="form-control form-control-sm" style="width:6rem" aria-label="Document required after days" placeholder="Never"></td>
                                <td><input type="hidden" name="is_paid" value="0"><input type="checkbox" class="form-check-input" name="is_paid" value="1" @checked($type->is_paid) aria-label="Paid"></td>
                                <td><input type="hidden" name="is_convertible" value="0"><input type="checkbox" class="form-check-input" name="is_convertible" value="1" @checked($type->is_convertible) aria-label="Convertible"></td>
                                <td><button class="btn btn-sm btn-outline-primary">Save</button></td>
                            </form>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <form method="post" action="{{ route('leave-types.store') }}" class="card">
        @csrf
        <div class="card-header"><h3 class="card-title">Add leave type</h3></div>
        <div class="card-body row g-3">
            <x-form.input name="code" label="Code" col="col-md-2" required />
            <x-form.input name="name" label="Name" col="col-md-4" required />
            <x-form.input name="days_per_year" label="Days / year" type="number" min="0" value="0" col="col-md-2" required />
            <x-form.input name="accrual_per_month" label="Accrual / month" type="number" step="0.01" min="0" value="0" col="col-md-2" required />
            <x-form.input name="carry_over_cap" label="Carry-over cap" type="number" min="0" value="0" col="col-md-2" required />
            <div class="col-12">
                <div class="form-check form-check-inline"><input type="hidden" name="is_paid" value="0"><input class="form-check-input" type="checkbox" id="new_is_paid" name="is_paid" value="1" checked><label class="form-check-label" for="new_is_paid">Paid</label></div>
                <div class="form-check form-check-inline"><input type="hidden" name="is_convertible" value="0"><input class="form-check-input" type="checkbox" id="new_is_convertible" name="is_convertible" value="1"><label class="form-check-label" for="new_is_convertible">Convertible to cash</label></div>
            </div>
        </div>
        <div class="card-footer"><button class="btn btn-primary">Add</button></div>
    </form>
@stop
