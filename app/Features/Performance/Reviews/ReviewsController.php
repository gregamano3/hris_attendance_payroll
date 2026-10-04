<?php

namespace App\Features\Performance\Reviews;

use App\Features\Employees\Queries\EmployeeDirectory;
use App\Features\Performance\Models\PerformanceReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Employees write a self-assessment and acknowledge completed reviews;
 * reviewers (supervisor, or HR with performance.manage) rate and comment.
 */
class ReviewsController
{
    public function __construct(private EmployeeDirectory $directory) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $me = $this->directory->forUser($user);

        return view('performance::reviews.index', [
            'mine' => $me ? PerformanceReview::query()->with('cycle')->where('employee_id', $me->id)->latest()->get() : collect(),
            'toReview' => PerformanceReview::query()->with(['cycle', 'employee'])
                ->whereHas('cycle', fn ($q) => $q->where('status', 'open'))
                ->where('status', 'pending')
                ->where('reviewer_id', $user?->id)
                ->get(),
        ]);
    }

    public function show(Request $request, PerformanceReview $review): View
    {
        $this->authorizeView($request, $review);

        return view('performance::reviews.show', [
            'review' => $review->load(['cycle', 'employee', 'reviewer']),
            'canReview' => $this->canReview($request, $review),
            'isEmployee' => $this->isEmployee($request, $review),
        ]);
    }

    public function selfAssessment(Request $request, PerformanceReview $review): RedirectResponse
    {
        abort_unless($this->isEmployee($request, $review) && $review->status === 'pending', 403);
        $review->update($request->validate(['self_assessment' => ['required', 'string', 'max:10000']]));

        return back()->with('success', 'Self-assessment saved.');
    }

    public function complete(Request $request, PerformanceReview $review): RedirectResponse
    {
        abort_unless($this->canReview($request, $review) && $review->status === 'pending' && $review->cycle->status === 'open', 403);

        $criteria = $review->cycle->criteria;
        $rules = ['comments' => ['required', 'string', 'max:10000']];

        foreach ($criteria as $i => $criterion) {
            $rules["ratings.{$i}"] = ['required', 'integer', 'between:1,5'];
        }

        $data = $request->validate($rules);
        $ratings = [];

        foreach ($criteria as $i => $criterion) {
            $ratings[$criterion['name']] = (int) $data['ratings'][$i];
        }

        $review->update([
            'ratings' => $ratings,
            'overall_rating' => PerformanceReview::weightedScore($criteria, $ratings),
            'comments' => $data['comments'],
            'status' => 'completed',
            'completed_at' => now(),
            'reviewer_id' => $review->reviewer_id ?? $request->user()?->id,
        ]);

        return redirect()->route('performance.reviews.index')->with('success', 'Review completed.');
    }

    public function acknowledge(Request $request, PerformanceReview $review): RedirectResponse
    {
        abort_unless($this->isEmployee($request, $review) && $review->status === 'completed', 403);
        $review->update(['status' => 'acknowledged', 'acknowledged_at' => now()]);

        return back()->with('success', 'Review acknowledged.');
    }

    private function isEmployee(Request $request, PerformanceReview $review): bool
    {
        return $this->directory->forUser($request->user())?->id === $review->employee_id;
    }

    private function canReview(Request $request, PerformanceReview $review): bool
    {
        $user = $request->user();

        return ! $this->isEmployee($request, $review)
            && ($review->reviewer_id === $user?->id || (bool) $user?->can('performance.manage'));
    }

    private function authorizeView(Request $request, PerformanceReview $review): void
    {
        abort_unless($this->isEmployee($request, $review) || $this->canReview($request, $review), 403);
    }
}
