<?php

namespace App\Features\Recruitment\Models;

use App\Shared\Audit\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property list<array{title: string, due_after_days: int}> $items
 * @property bool $is_default
 */
#[Fillable(['name', 'items', 'is_default'])]
class OnboardingTemplate extends Model
{
    use Auditable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['items' => 'array', 'is_default' => 'boolean'];
    }
}
