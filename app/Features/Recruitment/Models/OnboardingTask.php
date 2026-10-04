<?php

namespace App\Features\Recruitment\Models;

use App\Features\Employees\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $employee_id
 * @property string $title
 * @property Carbon|null $due_on
 * @property Carbon|null $completed_at
 * @property int|null $completed_by
 * @property int $sort
 * @property-read Employee $employee
 * @property-read User|null $completer
 */
#[Fillable(['employee_id', 'title', 'due_on', 'completed_at', 'completed_by', 'sort'])]
class OnboardingTask extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['due_on' => 'date', 'completed_at' => 'datetime'];
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}
