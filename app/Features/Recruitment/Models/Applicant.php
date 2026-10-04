<?php

namespace App\Features\Recruitment\Models;

use App\Features\Employees\Models\Employee;
use App\Features\Recruitment\Enums\ApplicantStage;
use App\Shared\Audit\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $job_opening_id
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string|null $mobile
 * @property string|null $source
 * @property ApplicantStage $stage
 * @property string|null $resume_path
 * @property string|null $resume_name
 * @property int|null $employee_id
 * @property Carbon|null $stage_changed_at
 * @property-read JobOpening $opening
 * @property-read Employee|null $employee
 */
#[Fillable(['job_opening_id', 'first_name', 'last_name', 'email', 'mobile', 'source', 'stage', 'resume_path', 'resume_name', 'employee_id', 'stage_changed_at'])]
class Applicant extends Model
{
    use Auditable;

    protected static function booted(): void
    {
        static::deleted(fn (self $applicant) => $applicant->resume_path && Storage::disk('local')->delete($applicant->resume_path));
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return ['stage' => ApplicantStage::class, 'mobile' => 'encrypted', 'stage_changed_at' => 'datetime'];
    }

    /**
     * @return BelongsTo<JobOpening, $this>
     */
    public function opening(): BelongsTo
    {
        return $this->belongsTo(JobOpening::class, 'job_opening_id');
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return HasMany<ApplicantEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(ApplicantEvent::class)->latest('id');
    }

    public function fullName(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
