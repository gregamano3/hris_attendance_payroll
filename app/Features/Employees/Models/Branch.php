<?php

namespace App\Features\Employees\Models;

use App\Shared\Audit\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Work location with its own regional/local holidays, and the clock-in
 * restrictions used by the attendance time clock.
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $address
 * @property string|null $latitude
 * @property string|null $longitude
 * @property int|null $geofence_radius_m
 * @property string|null $allowed_ip_ranges
 */
#[Fillable(['code', 'name', 'address', 'latitude', 'longitude', 'geofence_radius_m', 'allowed_ip_ranges'])]
class Branch extends Model
{
    use Auditable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['geofence_radius_m' => 'integer'];
    }

    /**
     * @return HasMany<Employee, $this>
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
