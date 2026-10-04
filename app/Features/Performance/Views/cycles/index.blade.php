@extends('layouts.app')

@section('title', 'Review cycles')

@section('page')
    <div class="row">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-body p-0">
                    <table class="table mb-0 align-middle">
                        <thead><tr><th>Cycle</th><th>Period</th><th>Due</th><th style="width: 30%">Completed</th><th>Status</th></tr></thead>
                        <tbody>
                            @forelse ($cycles as $cycle)
                                @php $pct = $cycle->reviews_count ? round($cycle->done_count / $cycle->reviews_count * 100) : 0; @endphp
                                <tr>
                                    <td><a href="{{ route('performance.cycles.show', $cycle) }}">{{ $cycle->name }}</a></td>
                                    <td class="small">{{ $cycle->period_start->format('M j, Y') }} – {{ $cycle->period_end->format('M j, Y') }}</td>
                                    <td>{{ $cycle->due_on->format('M j') }}</td>
                                    <td><div class="progress"><div class="progress-bar" style="width: {{ $pct }}%">{{ $cycle->done_count }}/{{ $cycle->reviews_count }}</div></div></td>
                                    <td><span class="badge text-bg-{{ $cycle->status === 'open' ? 'success' : 'secondary' }}">{{ ucfirst($cycle->status) }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-body-secondary py-4">No review cycles yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <form method="post" action="{{ route('performance.cycles.store') }}" class="card">
                @csrf
                <div class="card-header"><h3 class="card-title">Launch a review cycle</h3></div>
                <div class="card-body row g-2">
                    <x-form.input name="name" label="Name" col="col-12" placeholder="2026 annual review" required />
                    <x-form.input name="period_start" label="Period from" type="date" col="col-6" required />
                    <x-form.input name="period_end" label="Period to" type="date" col="col-6" required />
                    <x-form.input name="due_on" label="Due" type="date" col="col-6" required />
                    <x-form.textarea name="criteria" label="Criteria (one per line, “| weight”)" rows="6" required
                        value="Quality of work | 3&#10;Productivity | 3&#10;Teamwork | 2&#10;Attendance and punctuality | 1&#10;Initiative | 1" />
                </div>
                <div class="card-footer"><button class="btn btn-primary">Launch</button>
                    <div class="small text-body-secondary mt-1">One review per active employee, assigned to their supervisor (HR reviews employees without one).</div></div>
            </form>
        </div>
    </div>
@stop
