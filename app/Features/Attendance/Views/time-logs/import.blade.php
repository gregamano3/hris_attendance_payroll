@extends('layouts.app')

@section('title', 'Import time logs')

@section('page')
    @if (session('import_errors'))
        <div class="alert alert-warning">
            <strong>Some rows were skipped:</strong>
            <ul class="mb-0">
                @foreach (session('import_errors') as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-6">
            <form method="post" action="{{ route('attendance.import.store') }}" enctype="multipart/form-data" class="card">
                @csrf
                <div class="card-body">
                    <label for="file" class="form-label">CSV file</label>
                    <input type="file" id="file" name="file" accept=".csv,text/csv" class="form-control @error('file') is-invalid @enderror" required>
                    @error('file') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="card-footer d-flex gap-2">
                    <button class="btn btn-primary"><i class="bi bi-upload me-1"></i> Import</button>
                    <a href="{{ route('attendance.logs.index') }}" class="btn btn-outline-secondary">Back to time logs</a>
                </div>
            </form>
        </div>
        <div class="col-lg-6">
            <div class="callout callout-info">
                <h5>Expected format</h5>
                <p>One punch per row with a header line. Duplicate punches are skipped automatically.</p>
<pre class="mb-0">employee_no,logged_at,type
EMP-00001,2026-10-05 07:58,in
EMP-00001,2026-10-05 17:04,out</pre>
            </div>
        </div>
    </div>
@stop
