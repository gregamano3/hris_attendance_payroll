@extends('layouts.app')

@section('title', 'Time clock')

@section('page')
    @if (! $employee)
        <div class="callout callout-info">Your account is not linked to an employee record. Please contact Human Resources.</div>
    @else
        <div class="row">
            <div class="col-lg-5">
                <div class="card card-primary card-outline text-center">
                    <div class="card-body py-5">
                        <div class="text-body-secondary">{{ now()->format('l, F j, Y') }}</div>
                        <div class="display-4 fw-semibold my-3" id="live-clock">{{ now()->format('g:i:s A') }}</div>
                        <form method="post" action="{{ route('attendance.clock.store') }}">
                            @csrf
                            <button type="submit" id="punch-button"
                                class="btn btn-lg px-5 {{ $nextType->value === 'in' ? 'btn-success' : 'btn-danger' }}">
                                <i class="bi {{ $nextType->value === 'in' ? 'bi-box-arrow-in-right' : 'bi-box-arrow-right' }} me-1"></i>
                                {{ $nextType->value === 'in' ? 'Clock in' : 'Clock out' }}
                            </button>
                        </form>
                        <div class="small text-body-secondary mt-3">{{ $employee->full_name }} · {{ $employee->employee_no }}</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Recent punches</h3></div>
                    <div class="card-body p-0">
                        <table class="table mb-0">
                            <thead><tr><th>Date</th><th>Time</th><th>Type</th><th>Source</th></tr></thead>
                            <tbody>
                                @forelse ($logs as $log)
                                    <tr>
                                        <td>{{ $log->logged_at->format('D, M j') }}</td>
                                        <td>{{ $log->logged_at->format('g:i A') }}</td>
                                        <td><span class="badge text-bg-{{ $log->type->value === 'in' ? 'success' : 'danger' }}">{{ $log->type->label() }}</span></td>
                                        <td>{{ $log->source->label() }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-body-secondary py-4">No punches yet today.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif
@stop

@push('js')
    <script>
        setInterval(() => {
            const el = document.getElementById('live-clock');
            if (el) el.textContent = new Date().toLocaleTimeString('en-PH', { timeZone: @json(config('app.timezone')) });
        }, 1000);
    </script>
@endpush
