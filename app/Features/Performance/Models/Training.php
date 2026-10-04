<?php

namespace App\Features\Performance\Models;

use App\Features\Employees\Models\Employee;
use App\Shared\Audit\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $employee_id
 * @property string $title
 * @property string|null $provider
 * @property Carbon $completed_on
 * @property string|null $hours
 * @property Carbon|null $expires_on
 * @property string|null $certificate_path
 * @property string|null $certificate_name
 * @property-read Employee $employee
 */
#[Fillable(['employee_id', 'title', 'provider', 'completed_on', 'hours', 'expires_on', 'certificate_path', 'certificate_name'])]
class Training extends Model
{
    use Auditable;

    protected static function booted(): void
    {
        static::deleted(fn (self $training) => $training->certificate_path && Storage::disk('local')->delete($training->certificate_path));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['completed_on' => 'date', 'expires_on' => 'date'];
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }
}
