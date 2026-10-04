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
                        <form method="post" action="{{ route('attendance.clock.store') }}" id="clock-form" @if ($requiresLocation) data-requires-location @endif>
                            @csrf
                            <input type="hidden" name="latitude" id="clock-latitude">
                            <input type="hidden" name="longitude" id="clock-longitude">
                            @if ($nextType->value === 'break_in')
                                <button type="submit" name="action" value="break" id="punch-button" class="btn btn-lg px-5 btn-warning">
                                    <i class="bi bi-cup-hot me-1"></i> End break
                                </button>
                            @else
                                <button type="submit" id="punch-button"
                                    class="btn btn-lg px-5 {{ $nextType->value === 'in' ? 'btn-success' : 'btn-danger' }}">
                                    <i class="bi {{ $nextType->value === 'in' ? 'bi-box-arrow-in-right' : 'bi-box-arrow-right' }} me-1"></i>
                                    {{ $nextType->value === 'in' ? 'Clock in' : 'Clock out' }}
                                </button>
                                @if ($breakAction?->value === 'break_out')
                                    <button type="submit" name="action" value="break" class="btn btn-lg btn-outline-warning ms-2" id="break-button">
                                        <i class="bi bi-cup-hot me-1"></i> Start break
                                    </button>
                                @endif
                            @endif
                        </form>
                        <div class="small text-body-secondary mt-3">{{ $employee->full_name }} · {{ $employee->employee_no }}</div>
                        @if ($requiresLocation)
                            <div class="small text-body-secondary mt-1"><i class="bi bi-geo-alt"></i> Your location is checked against {{ $employee->branch->name }} when you clock in; only the distance is stored.</div>
                        @endif
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
                                        <td><span class="badge text-bg-{{ ['in' => 'success', 'out' => 'danger'][$log->type->value] ?? 'warning' }}">{{ $log->type->label() }}</span></td>
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
        document.getElementById('clock-form')?.addEventListener('submit', function (event) {
            if (! this.hasAttribute('data-requires-location') || this.dataset.located) return;
            event.preventDefault();
            const form = this;
            const submit = () => { form.dataset.located = '1'; form.submit(); };
            if (! navigator.geolocation) return submit();
            navigator.geolocation.getCurrentPosition((position) => {
                document.getElementById('clock-latitude').value = position.coords.latitude;
                document.getElementById('clock-longitude').value = position.coords.longitude;
                submit();
            }, submit, { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 });
        });

        setInterval(() => {
            const el = document.getElementById('live-clock');
            if (el) el.textContent = new Date().toLocaleTimeString('en-PH', { timeZone: @json(config('app.timezone')) });
        }, 1000);
    </script>
@endpush
