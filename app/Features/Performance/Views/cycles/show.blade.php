@extends('layouts.app')

@section('title', $cycle->name)

@section('page_actions')
    <form method="post" action="{{ route('performance.cycles.close', $cycle) }}">@csrf @method('patch')
        <button class="btn btn-outline-secondary">{{ $cycle->status === 'open' ? 'Close cycle' : 'Reopen cycle' }}</button></form>
@stop

@section('page')
    <div class="card">
        <div class="card-body p-0 table-responsive">
            <table class="table table-sm table-striped mb-0 align-middle">
                <thead><tr><th>Employee</th><th>Reviewer</th><th>Status</th><th class="text-end">Rating</th><th></th></tr></thead>
                <tbody>
                    @foreach ($reviews as $review)
                        <tr>
                            <td>{{ $review->employee->full_name }}</td>
                            <td>{{ $review->reviewer?->name ?? 'HR' }}</td>
                            <td><span class="badge text-bg-{{ ['pending' => 'warning', 'completed' => 'info', 'acknowledged' => 'success'][$review->status] }}">{{ ucfirst($review->status) }}</span></td>
                            <td class="text-end">{{ $review->overall_rating ?? '—' }}</td>
                            <td class="actions"><a href="{{ route('performance.reviews.show', $review) }}" class="btn btn-sm btn-outline-primary">Open</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@stop
