<?php

namespace App\Features\Employees\Models;

use App\Features\Employees\Enums\CivilStatus;
use App\Features\Employees\Enums\EmploymentStatus;
use App\Features\Employees\Enums\EmploymentType;
use App\Features\Employees\Enums\Gender;
use App\Features\Employees\Enums\GovernmentId;
use App\Features\Employees\Enums\RateType;
use App\Features\Payroll\Models\FinalPay;
use App\Models\User;
use App\Shared\Audit\Auditable;
use App\Shared\Money\Money;
use App\Shared\Money\MoneyCast;
use App\Shared\Security\BlindIndex;
use App\Shared\Security\EncryptedDate;
use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $employee_no
 * @property int|null $user_id
 * @property string $first_name
 * @property string|null $middle_name
 * @property string $last_name
 * @property string|null $suffix
 * @property Carbon|null $birth_date
 * @property Gender|null $gender
 * @property CivilStatus|null $civil_status
 * @property string|null $email
 * @property string|null $mobile
 * @property string|null $address
 * @property int|null $department_id
 * @property int|null $position_id
 * @property EmploymentType $employment_type
 * @property EmploymentStatus $status
 * @property Carbon $hired_at
 * @property Carbon|null $regularized_at
 * @property Carbon|null $separated_at
 * @property RateType $rate_type
 * @property Money $basic_rate
 * @property bool $is_minimum_wage_earner
 * @property string|null $sss_no
 * @property string|null $philhealth_no
 * @property string|null $pagibig_no
 * @property string|null $tin
 * @property string|null $bank_name
 * @property string|null $bank_account_name
 * @property string|null $bank_account_no
 * @property-read string $full_name
 * @property-read Department|null $department
 * @property-read Position|null $position
 * @property-read User|null $user
 */
#[Fillable([
    'employee_no', 'user_id', 'first_name', 'middle_name', 'last_name', 'suffix', 'birth_date', 'gender',
    'civil_status', 'email', 'mobile', 'address', 'department_id', 'position_id', 'employment_type', 'status',
    'hired_at', 'regularized_at', 'separated_at', 'rate_type', 'basic_rate', 'is_minimum_wage_earner', 'sss_no', 'philhealth_no',
    'pagibig_no', 'tin', 'bank_name', 'bank_account_name', 'bank_account_no',
])]
#[UseFactory(EmployeeFactory::class)]
class Employee extends Model
{
    use Auditable;

    /** @use HasFactory<EmployeeFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            // PII encrypted at rest (see SECURITY.md)
            'birth_date' => EncryptedDate::class,
            'mobile' => 'encrypted',
            'address' => 'encrypted',
            'sss_no' => 'encrypted',
            'philhealth_no' => 'encrypted',
            'pagibig_no' => 'encrypted',
            'tin' => 'encrypted',
            'bank_account_name' => 'encrypted',
            'bank_account_no' => 'encrypted',
            'hired_at' => 'date',
            'regularized_at' => 'date',
            'separated_at' => 'date',
            'gender' => Gender::class,
            'civil_status' => CivilStatus::class,
            'employment_type' => EmploymentType::class,
            'status' => EmploymentStatus::class,
            'rate_type' => RateType::class,
            'basic_rate' => MoneyCast::class,
            'is_minimum_wage_earner' => 'boolean',
        ];
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
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted(): void
    {
        // Keep the blind indexes of the encrypted government IDs in sync.
        static::saving(function (self $employee) {
            foreach (GovernmentId::cases() as $id) {
                if ($employee->isDirty($id->value) || ! $employee->exists) {
                    $employee->setAttribute($id->blindIndexColumn(), BlindIndex::hash($id->value, $employee->getAttribute($id->value)));
                }
            }
        });
    }

    /**
     * Find an employee by a government ID without decrypting every row.
     */
    public static function findByGovernmentId(GovernmentId $id, string $value): ?self
    {
        return static::withTrashed()->where($id->blindIndexColumn(), BlindIndex::hash($id->value, GovernmentId::normalize($value)))->first();
    }

    /**
     * @return HasMany<CompensationChange, $this>
     */
    public function compensationChanges(): HasMany
    {
        return $this->hasMany(CompensationChange::class)->orderByDesc('effective_from');
    }

    /**
     * @return HasMany<EmployeeDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class)->latest();
    }

    /**
     * @return HasOne<FinalPay, $this>
     */
    public function finalPay(): HasOne
    {
        return $this->hasOne(FinalPay::class);
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('status', EmploymentStatus::Active);
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function search(Builder $query, string $term): void
    {
        $query->where(function (Builder $query) use ($term) {
            $like = "%{$term}%";
            $query->whereLike('employee_no', $like, caseSensitive: false)
                ->orWhereLike('first_name', $like, caseSensitive: false)
                ->orWhereLike('last_name', $like, caseSensitive: false)
                ->orWhereRaw("concat(first_name, ' ', last_name) ilike ?", [$like]);
        });
    }

    /**
     * "Dela Cruz, Juan P. Jr."
     *
     * @return Attribute<string, never>
     */
    protected function fullName(): Attribute
    {
        return Attribute::get(fn (): string => $this->formatFullName());
    }

    private function formatFullName(): string
    {
        $middleInitial = $this->middle_name ? ' '.mb_substr($this->middle_name, 0, 1).'.' : '';
        $suffix = $this->suffix ? ' '.$this->suffix : '';

        return "{$this->last_name}, {$this->first_name}{$middleInitial}{$suffix}";
    }

    public function hasBankAccount(): bool
    {
        return filled($this->bank_account_no) && filled($this->bank_name);
    }

    public function governmentId(GovernmentId $id): string
    {
        return $id->format($this->getAttribute($id->value));
    }
}
