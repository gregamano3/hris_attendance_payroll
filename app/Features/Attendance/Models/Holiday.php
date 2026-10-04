<?php

namespace App\Features\Attendance\Models;

use App\Features\Attendance\Enums\HolidayType;
use App\Features\Employees\Models\Branch;
use App\Shared\Audit\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property Carbon $date
 * @property string $name
 * @property HolidayType $type
 * @property int|null $branch_id
 * @property-read Branch|null $branch
 */
#[Fillable(['date', 'name', 'type', 'branch_id'])]
class Holiday extends Model
{
    use Auditable;

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'type' => HolidayType::class,
        ];
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
