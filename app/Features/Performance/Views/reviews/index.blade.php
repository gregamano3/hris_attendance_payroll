@extends('layouts.app')

@section('title', 'Performance reviews')

@section('page')
    @if ($toReview->isNotEmpty())
        <div class="card card-warning card-outline">
            <div class="card-header"><h3 class="card-title">To review</h3></div>
            <ul class="list-group list-group-flush">
                @foreach ($toReview as $review)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>{{ $review->employee->full_name }} <span class="small text-body-secondary">{{ $review->cycle->name }} · due {{ $review->cycle->due_on->format('M j') }}</span></span>
                        <a href="{{ route('performance.reviews.show', $review) }}" class="btn btn-sm btn-primary">Review</a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
    <div class="card">
        <div class="card-header"><h3 class="card-title">My reviews</h3></div>
        <ul class="list-group list-group-flush">
            @forelse ($mine as $review)
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span>{{ $review->cycle->name }} <span class="badge text-bg-light border">{{ ucfirst($review->status) }}</span>
                        @if ($review->overall_rating) <span class="small">Rating {{ $review->overall_rating }} / 5</span>@endif</span>
                    <a href="{{ route('performance.reviews.show', $review) }}" class="btn btn-sm btn-outline-primary">Open</a>
                </li>
            @empty
                <li class="list-group-item text-body-secondary">No reviews yet.</li>
            @endforelse
        </ul>
    </div>
@stop
