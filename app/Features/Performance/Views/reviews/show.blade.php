@php use App\Features\Performance\Models\PerformanceReview; @endphp
@extends('layouts.app')

@section('title', 'Review — '.$review->employee->full_name)

@section('page')
    <p class="text-body-secondary">{{ $review->cycle->name }} · {{ $review->cycle->period_start->format('M j, Y') }} – {{ $review->cycle->period_end->format('M j, Y') }}
        · Reviewer {{ $review->reviewer?->name ?? 'HR' }} · <span class="badge text-bg-light border">{{ ucfirst($review->status) }}</span></p>

    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Self-assessment</h3></div>
                @if ($isEmployee && $review->status === 'pending')
                    <form method="post" action="{{ route('performance.reviews.self', $review) }}">
                        @csrf @method('put')
                        <div class="card-body"><x-form.textarea name="self_assessment" label="Your accomplishments, challenges and goals" :value="$review->self_assessment" rows="8" required /></div>
                        <div class="card-footer"><button class="btn btn-outline-primary">Save</button></div>
                    </form>
                @else
                    <div class="card-body" style="white-space: pre-line">{{ $review->self_assessment ?: 'Not provided.' }}</div>
                @endif
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Evaluation</h3></div>
                @if ($canReview && $review->status === 'pending')
                    <form method="post" action="{{ route('performance.reviews.complete', $review) }}">
                        @csrf @method('put')
                        <div class="card-body row g-2">
                            @foreach ($review->cycle->criteria as $i => $criterion)
                                <x-form.select :name="'ratings['.$i.']'" :id="'rating_'.$i" :label="$criterion['name'].' (weight '.$criterion['weight'].')'"
                                    :options="PerformanceReview::RATING_LABELS" col="col-12" placeholder="Select…" required />
                            @endforeach
                            <x-form.textarea name="comments" label="Comments and development plan" rows="5" required />
                        </div>
                        <div class="card-footer"><button class="btn btn-primary" id="complete-review">Complete review</button></div>
                    </form>
                @elseif ($review->status === 'pending')
                    <div class="card-body text-body-secondary">The evaluation is not completed yet.</div>
                @else
                    <ul class="list-group list-group-flush">
                        @foreach ($review->ratings ?? [] as $name => $rating)
                            <li class="list-group-item d-flex justify-content-between"><span>{{ $name }}</span><strong>{{ $rating }} · {{ PerformanceReview::RATING_LABELS[$rating] ?? '' }}</strong></li>
                        @endforeach
                        <li class="list-group-item d-flex justify-content-between"><span>Overall (weighted)</span><strong>{{ $review->overall_rating }} / 5</strong></li>
                    </ul>
                    <div class="card-body" style="white-space: pre-line">{{ $review->comments }}</div>
                    @if ($isEmployee && $review->status === 'completed')
                        <form method="post" action="{{ route('performance.reviews.acknowledge', $review) }}" class="card-footer">@csrf @method('patch')
                            <button class="btn btn-success">I have read this review</button></form>
                    @elseif ($review->acknowledged_at)
                        <div class="card-footer small text-success">Acknowledged {{ $review->acknowledged_at->format('M j, Y') }}</div>
                    @endif
                @endif
            </div>
        </div>
    </div>
@stop
