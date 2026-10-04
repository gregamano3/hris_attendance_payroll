<?php

namespace App\Features\Employees\Models;

use App\Shared\Audit\Auditable;
use Database\Factories\PositionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int|null $department_id
 * @property string $title
 * @property string|null $description
 * @property-read Department|null $department
 */
#[Fillable(['department_id', 'title', 'description'])]
#[UseFactory(PositionFactory::class)]
class Position extends Model
{
    use Auditable;

    /** @use HasFactory<PositionFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * @return HasMany<Employee, $this>
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
