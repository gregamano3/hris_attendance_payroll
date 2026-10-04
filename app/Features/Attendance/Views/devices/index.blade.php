@extends('layouts.app')

@section('title', 'Biometric devices')

@section('page')
    @if (session('device_token'))
        <div class="alert alert-warning">
            <strong>Device token</strong> (shown once):
            <code id="device-token" class="d-block mt-1 user-select-all">{{ session('device_token') }}</code>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body p-0 table-responsive">
                    <table class="table table-striped mb-0 align-middle">
                        <thead><tr><th>Device</th><th>Branch</th><th>Last seen</th><th>Status</th><th class="actions"></th></tr></thead>
                        <tbody>
                            @forelse ($devices as $device)
                                <tr>
                                    <td>{{ $device->name }}</td>
                                    <td>{{ $device->branch?->name ?? '—' }}</td>
                                    <td class="small">{{ $device->last_seen_at?->diffForHumans() ?? 'Never' }} {{ $device->last_ip }}</td>
                                    <td><span class="badge text-bg-{{ $device->is_active ? 'success' : 'secondary' }}">{{ $device->is_active ? 'Active' : 'Disabled' }}</span></td>
                                    <td class="actions d-flex gap-1">
                                        <form method="post" action="{{ route('devices.regenerate', $device) }}" data-confirm="Generate a new token? The current one stops working.">
                                            @csrf <button class="btn btn-sm btn-outline-secondary">New token</button>
                                        </form>
                                        <form method="post" action="{{ route('devices.toggle', $device) }}">
                                            @csrf @method('patch') <button class="btn btn-sm btn-outline-{{ $device->is_active ? 'danger' : 'success' }}">{{ $device->is_active ? 'Disable' : 'Enable' }}</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-body-secondary py-4">No devices registered.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="callout callout-info small">
                <h5>Device API</h5>
<pre class="mb-0">POST {{ url('/api/attendance/punches') }}
Authorization: Bearer &lt;device token&gt;
Content-Type: application/json

{"punches": [{"employee_no": "EMP-00001", "timestamp": "2026-10-05T07:58:00+08:00", "type": "in"}]}</pre>
                Up to 500 punches per request; identical punches are reported as duplicates, so retries are safe.
            </div>
        </div>
        <div class="col-lg-4">
            <form method="post" action="{{ route('devices.store') }}" class="card">
                @csrf
                <div class="card-header"><h3 class="card-title">Register a device</h3></div>
                <div class="card-body row g-3">
                    <x-form.input name="name" label="Name" col="col-12" placeholder="Lobby terminal" required />
                    <x-form.select name="branch_id" label="Branch" :options="$branches" col="col-12" placeholder="—" />
                </div>
                <div class="card-footer"><button class="btn btn-primary">Register</button></div>
            </form>
        </div>
    </div>
@stop
