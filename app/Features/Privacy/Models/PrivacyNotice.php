<?php

namespace App\Features\Privacy\Models;

use App\Shared\Audit\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $version
 * @property string $title
 * @property string $body
 * @property Carbon|null $published_at
 */
#[Fillable(['version', 'title', 'body', 'published_at', 'created_by'])]
class PrivacyNotice extends Model
{
    use Auditable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    /**
     * @return HasMany<PrivacyAcknowledgement, $this>
     */
    public function acknowledgements(): HasMany
    {
        return $this->hasMany(PrivacyAcknowledgement::class);
    }

    public static function current(): ?self
    {
        return static::query()->whereNotNull('published_at')->where('published_at', '<=', now())->latest('published_at')->latest('id')->first();
    }
}
