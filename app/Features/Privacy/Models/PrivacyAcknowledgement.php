<?php

namespace App\Features\Privacy\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $privacy_notice_id
 * @property int $user_id
 * @property string|null $ip_address
 * @property Carbon $acknowledged_at
 */
#[Fillable(['privacy_notice_id', 'user_id', 'ip_address', 'acknowledged_at'])]
class PrivacyAcknowledgement extends Model
{
    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['acknowledged_at' => 'datetime'];
    }
}
