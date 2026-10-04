<?php

namespace App\Features\Attendance\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property bool $is_paid
 * @property bool $is_convertible
 * @property int $days_per_year
 */
#[Fillable(['code', 'name', 'is_paid', 'is_convertible', 'days_per_year'])]
class LeaveType extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['is_paid' => 'boolean', 'is_convertible' => 'boolean', 'days_per_year' => 'integer'];
    }

    public function hasYearlyCap(): bool
    {
        return $this->days_per_year > 0;
    }
}
