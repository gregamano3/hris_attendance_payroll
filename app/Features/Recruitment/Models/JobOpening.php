<?php

namespace App\Features\Recruitment\Models;

use App\Features\Employees\Enums\EmploymentType;
use App\Features\Employees\Models\Branch;
use App\Features\Employees\Models\Department;
use App\Features\Employees\Models\Position;
use App\Shared\Audit\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $title
 * @property int|null $department_id
 * @property int|null $position_id
 * @property int|null $branch_id
 * @property EmploymentType $employment_type
 * @property int $slots
 * @property string|null $description
 * @property string $status open | closed
 * @property Carbon|null $closed_at
 * @property-read Department|null $department
 * @property-read Position|null $position
 * @property-read Branch|null $branch
 */
#[Fillable(['title', 'department_id', 'position_id', 'branch_id', 'employment_type', 'slots', 'description', 'status', 'closed_at'])]
class JobOpening extends Model
{
    use Auditable;

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return ['employment_type' => EmploymentType::class, 'closed_at' => 'datetime', 'slots' => 'integer'];
    }

    /**
     * @return HasMany<Applicant, $this>
     */
    public function applicants(): HasMany
    {
        return $this->hasMany(Applicant::class);
    }

    /**
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * @return BelongsTo<Position, $this>
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
