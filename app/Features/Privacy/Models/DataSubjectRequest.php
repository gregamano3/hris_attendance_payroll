<?php

namespace App\Features\Privacy\Models;

use App\Features\Employees\Models\Employee;
use App\Features\Privacy\Enums\RequestStatus;
use App\Features\Privacy\Enums\RequestType;
use App\Models\User;
use App\Shared\Audit\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Request of a data subject under the Data Privacy Act (RA 10173, Sec. 16).
 *
 * @property int $id
 * @property int|null $user_id
 * @property int|null $employee_id
 * @property RequestType $type
 * @property RequestStatus $status
 * @property string|null $details
 * @property string|null $response
 * @property int|null $handled_by
 * @property Carbon|null $handled_at
 * @property-read User|null $user
 * @property-read Employee|null $employee
 * @property-read User|null $handler
 */
#[Fillable(['user_id', 'employee_id', 'type', 'status', 'details', 'response', 'handled_by', 'handled_at'])]
class DataSubjectRequest extends Model
{
    use Auditable;

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return ['type' => RequestType::class, 'status' => RequestStatus::class, 'handled_at' => 'datetime'];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}
