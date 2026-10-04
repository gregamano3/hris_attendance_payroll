<?php

namespace App\Features\Attendance\Models;

use App\Features\Employees\Models\Branch;
use App\Shared\Audit\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Biometric terminal or kiosk posting punches through the device API. Only
 * the SHA-256 hash of its token is stored.
 *
 * @property int $id
 * @property string $name
 * @property int|null $branch_id
 * @property string $token_hash
 * @property bool $is_active
 * @property Carbon|null $last_seen_at
 * @property string|null $last_ip
 * @property-read Branch|null $branch
 */
#[Fillable(['name', 'branch_id', 'token_hash', 'is_active', 'last_seen_at', 'last_ip'])]
#[Hidden(['token_hash'])]
class AttendanceDevice extends Model
{
    use Auditable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'last_seen_at' => 'datetime'];
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Generates a new token, stores its hash and returns the plain token once.
     */
    public function issueToken(): string
    {
        $token = 'hrisdev_'.Str::random(48);
        $this->forceFill(['token_hash' => hash('sha256', $token)])->save();

        return $token;
    }

    public static function findByToken(string $token): ?self
    {
        return static::query()->where('token_hash', hash('sha256', $token))->where('is_active', true)->first();
    }
}
