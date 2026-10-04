@php use App\Features\Privacy\Enums\RequestStatus; @endphp
@extends('layouts.app')

@section('title', 'Privacy requests')

@section('page')
    <ul class="nav nav-tabs mb-3">
        @foreach (RequestStatus::cases() as $case)
            <li class="nav-item"><a class="nav-link @if ($case === $status) active @endif" href="{{ route('privacy.requests.index', ['status' => $case->value]) }}">{{ $case->label() }}</a></li>
        @endforeach
    </ul>
    <div class="callout callout-info small">The Data Privacy Act expects requests to be acted on promptly. Erasure of employment records may be limited by legal retention requirements; explain this in your response.</div>
    <div class="card">
        <div class="card-body p-0 table-responsive">
            <table class="table table-striped mb-0 align-middle">
                <thead><tr><th>Received</th><th>From</th><th>Request</th><th>Details</th><th>{{ $status === RequestStatus::Open ? 'Action' : 'Response' }}</th></tr></thead>
                <tbody>
                    @forelse ($requests as $request)
                        <tr>
                            <td class="text-nowrap small">{{ $request->created_at->format('M j, Y') }}</td>
                            <td>{{ $request->employee?->full_name ?? $request->user?->name ?? '—' }}</td>
                            <td>{{ $request->type->label() }}</td>
                            <td class="small">{{ $request->details }}</td>
                            <td>
                                @if ($status === RequestStatus::Open)
                                    <form method="post" action="{{ route('privacy.requests.update', $request) }}" class="d-flex gap-1">
                                        @csrf @method('patch')
                                        <input name="response" class="form-control form-control-sm" placeholder="Response to the requester" aria-label="Response" required>
                                        <button name="status" value="completed" class="btn btn-sm btn-success">Complete</button>
                                        <button name="status" value="rejected" class="btn btn-sm btn-outline-secondary">Reject</button>
                                    </form>
                                @else
                                    <span class="small">{{ $request->response }}</span>
                                    <div class="small text-body-secondary">{{ $request->handler?->name }} · {{ $request->handled_at?->format('M j, Y') }}</div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-body-secondary py-4">Nothing here.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($requests->hasPages())<div class="card-footer">{{ $requests->links() }}</div>@endif
    </div>
@stop
