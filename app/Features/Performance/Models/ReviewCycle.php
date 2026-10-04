<?php

namespace App\Features\Performance\Models;

use App\Shared\Audit\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property Carbon $period_start
 * @property Carbon $period_end
 * @property Carbon $due_on
 * @property list<array{name: string, weight: int}> $criteria
 * @property string $status open | closed
 */
#[Fillable(['name', 'period_start', 'period_end', 'due_on', 'criteria', 'status'])]
class ReviewCycle extends Model
{
    use Auditable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['period_start' => 'date', 'period_end' => 'date', 'due_on' => 'date', 'criteria' => 'array'];
    }

    /**
     * @return HasMany<PerformanceReview, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(PerformanceReview::class);
    }
}
