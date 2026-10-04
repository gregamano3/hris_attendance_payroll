<?php

namespace App\Features\Recruitment\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $applicant_id
 * @property string|null $from_stage
 * @property string|null $to_stage
 * @property string|null $note
 * @property int|null $user_id
 * @property Carbon $created_at
 * @property-read User|null $user
 */
#[Fillable(['applicant_id', 'from_stage', 'to_stage', 'note', 'user_id'])]
class ApplicantEvent extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
