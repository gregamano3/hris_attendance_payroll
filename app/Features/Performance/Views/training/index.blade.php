@extends('layouts.app')

@section('title', 'Training records')

@section('page')
    @if ($expiring->isNotEmpty())
        <div class="callout callout-warning">
            <strong>Certifications expiring (or expired in the last 30 days):</strong>
            {{ $expiring->map(fn ($t) => $t->employee->full_name.' — '.$t->title.' ('.$t->expires_on->format('M j, Y').')')->implode('; ') }}
        </div>
    @endif
    <div class="row">
        <div class="col-lg-8">
            <form method="get" class="card card-body flex-row gap-2 mb-3">
                <select name="employee" class="form-select" aria-label="Employee"><option value="">All employees</option>
                    @foreach ($employees as $id => $label)<option value="{{ $id }}" @selected($employeeId === $id)>{{ $label }}</option>@endforeach
                </select>
                <button class="btn btn-outline-secondary">Filter</button>
            </form>
            <div class="card">
                <div class="card-body p-0 table-responsive">
                    <table class="table table-sm table-striped mb-0 align-middle">
                        <thead><tr><th>Employee</th><th>Training</th><th>Completed</th><th class="text-end">Hours</th><th>Expires</th><th class="actions"></th></tr></thead>
                        <tbody>
                            @forelse ($trainings as $training)
                                <tr>
                                    <td>{{ $training->employee->full_name }}</td>
                                    <td>{{ $training->title }} <span class="small text-body-secondary">{{ $training->provider }}</span>
                                        @if ($training->certificate_path)<a href="{{ route('performance.trainings.certificate', $training) }}"><i class="bi bi-patch-check"></i></a>@endif</td>
                                    <td>{{ $training->completed_on->format('M j, Y') }}</td>
                                    <td class="text-end">{{ $training->hours ? (float) $training->hours : '—' }}</td>
                                    <td>{{ $training->expires_on?->format('M j, Y') ?? '—' }}</td>
                                    <td class="actions"><x-delete-button :action="route('performance.trainings.destroy', $training)" label="Remove" confirm="Remove this record?" /></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-body-secondary py-4">No training records.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($trainings->hasPages())<div class="card-footer">{{ $trainings->links() }}</div>@endif
            </div>
        </div>
        <div class="col-lg-4">
            <form method="post" action="{{ route('performance.trainings.store') }}" enctype="multipart/form-data" class="card">
                @csrf
                <div class="card-header"><h3 class="card-title">Record training</h3></div>
                <div class="card-body row g-2">
                    <x-form.select name="employee_id" label="Employee" :options="$employees" col="col-12" placeholder="Select…" required />
                    <x-form.input name="title" label="Training / certification" col="col-12" required />
                    <x-form.input name="provider" label="Provider" col="col-12" />
                    <x-form.input name="completed_on" label="Completed" type="date" col="col-6" required />
                    <x-form.input name="hours" label="Hours" type="number" step="0.5" min="0" col="col-6" />
                    <x-form.input name="expires_on" label="Expires (certifications)" type="date" col="col-12" />
                    <div class="col-12"><label for="certificate" class="form-label">Certificate</label>
                        <input type="file" id="certificate" name="certificate" accept=".pdf,.jpg,.jpeg,.png" class="form-control"></div>
                </div>
                <div class="card-footer"><button class="btn btn-primary">Save</button></div>
            </form>
        </div>
    </div>
@stop
