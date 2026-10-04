<?php

namespace App\Features\Attendance\Models;

use App\Features\Attendance\Enums\TimeLogSource;
use App\Features\Attendance\Enums\TimeLogType;
use App\Features\Employees\Models\Employee;
use App\Models\User;
use App\Shared\Audit\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Raw punch. Removed entries are soft deleted with the remover recorded so
 * the audit trail is preserved.
 *
 * @property int $id
 * @property int $employee_id
 * @property Carbon $logged_at
 * @property TimeLogType $type
 * @property TimeLogSource $source
 * @property string|null $remarks
 * @property string|null $ip_address
 * @property int|null $created_by
 * @property int|null $deleted_by
 * @property-read Employee $employee
 * @property-read User|null $creator
 */
#[Fillable(['employee_id', 'logged_at', 'type', 'source', 'remarks', 'ip_address', 'created_by'])]
class TimeLog extends Model
{
    use Auditable, SoftDeletes;

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'logged_at' => 'datetime',
            'type' => TimeLogType::class,
            'source' => TimeLogSource::class,
        ];
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
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
