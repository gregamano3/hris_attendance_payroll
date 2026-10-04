<?php

namespace App\Features\Performance\Models;

use App\Features\Employees\Models\Employee;
use App\Models\User;
use App\Shared\Audit\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $review_cycle_id
 * @property int $employee_id
 * @property int|null $reviewer_id
 * @property string|null $self_assessment
 * @property array<string, int>|null $ratings
 * @property string|null $overall_rating
 * @property string|null $comments
 * @property string $status pending | completed | acknowledged
 * @property Carbon|null $completed_at
 * @property Carbon|null $acknowledged_at
 * @property-read ReviewCycle $cycle
 * @property-read Employee $employee
 * @property-read User|null $reviewer
 */
#[Fillable(['review_cycle_id', 'employee_id', 'reviewer_id', 'self_assessment', 'ratings', 'overall_rating', 'comments', 'status', 'completed_at', 'acknowledged_at'])]
class PerformanceReview extends Model
{
    use Auditable;

    public const RATING_LABELS = [1 => 'Needs improvement', 2 => 'Partially meets', 3 => 'Meets expectations', 4 => 'Exceeds', 5 => 'Outstanding'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['ratings' => 'array', 'completed_at' => 'datetime', 'acknowledged_at' => 'datetime'];
    }

    /**
     * @return BelongsTo<ReviewCycle, $this>
     */
    public function cycle(): BelongsTo
    {
        return $this->belongsTo(ReviewCycle::class, 'review_cycle_id');
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /**
     * Weighted average of the criterion ratings (1–5).
     *
     * @param  list<array{name: string, weight: int}>  $criteria
     * @param  array<string, int>  $ratings
     */
    public static function weightedScore(array $criteria, array $ratings): float
    {
        $total = array_sum(array_column($criteria, 'weight')) ?: 1;
        $score = 0.0;

        foreach ($criteria as $criterion) {
            $score += ($ratings[$criterion['name']] ?? 0) * $criterion['weight'];
        }

        return round($score / $total, 2);
    }
}
