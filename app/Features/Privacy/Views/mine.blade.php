@extends('layouts.app')

@section('title', 'My privacy')

@section('page')
    <div class="row">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Your data</h3></div>
                <div class="card-body">
                    <p>Download a copy of the personal data this system holds about you (account, employee record, attendance, leaves, payslips) as a JSON file.</p>
                    <a href="{{ route('privacy.export') }}" class="btn btn-outline-primary" id="export-button"><i class="bi bi-download me-1"></i> Download my data</a>
                    <p class="mt-3 mb-0"><a href="{{ route('privacy.notice') }}">Read the privacy notice</a></p>
                </div>
            </div>
            <form method="post" action="{{ route('privacy.requests.store') }}" class="card">
                @csrf
                <div class="card-header"><h3 class="card-title">Make a request</h3></div>
                <div class="card-body row g-3">
                    <x-form.select name="type" label="Request" :options="$types" col="col-12" required />
                    <x-form.textarea name="details" label="Details" rows="4" required />
                </div>
                <div class="card-footer"><button class="btn btn-primary">Send to the Data Protection Officer</button></div>
            </form>
        </div>
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header"><h3 class="card-title">My requests</h3></div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Date</th><th>Request</th><th>Status</th><th>Response</th></tr></thead>
                        <tbody>
                            @forelse ($requests as $request)
                                <tr>
                                    <td class="text-nowrap">{{ $request->created_at->format('M j, Y') }}</td>
                                    <td>{{ $request->type->label() }}</td>
                                    <td><span class="badge text-bg-{{ $request->status->badge() }}">{{ $request->status->label() }}</span></td>
                                    <td class="small">{{ $request->response }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-body-secondary py-3">No requests.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@stop
