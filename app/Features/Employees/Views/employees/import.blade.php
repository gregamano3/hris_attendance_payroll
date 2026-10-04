@extends('layouts.app')

@section('title', 'Import employees')

@section('page')
    @if (session('import_errors'))
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach (session('import_errors') as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-5">
            <form method="post" action="{{ route('employees.import.store') }}" enctype="multipart/form-data" class="card">
                @csrf
                <div class="card-body">
                    <label for="file" class="form-label">CSV file</label>
                    <input type="file" id="file" name="file" accept=".csv,text/csv" class="form-control @error('file') is-invalid @enderror" required>
                    @error('file') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <p class="small text-body-secondary mt-2 mb-0">All rows are validated first. If any row has an error, nothing is imported.</p>
                </div>
                <div class="card-footer d-flex gap-2">
                    <button class="btn btn-primary"><i class="bi bi-upload me-1"></i> Import</button>
                    <a href="{{ route('employees.import.template') }}" class="btn btn-outline-secondary"><i class="bi bi-download me-1"></i> Template</a>
                </div>
            </form>
        </div>
        <div class="col-lg-7">
            <div class="callout callout-info small">
                <h5>Columns</h5>
                <p class="mb-2">Required: <code>employee_no, first_name, last_name, employment_type, hired_at, rate_type, basic_rate</code>.
                    <code>department_code</code> and <code>position_title</code> must match existing records. Status defaults to <em>active</em>.</p>
                <p class="mb-0"><code>{{ implode(', ', $columns) }}</code></p>
            </div>
        </div>
    </div>
@stop
