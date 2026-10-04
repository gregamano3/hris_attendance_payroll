<?php

namespace App\Features\Attendance\Models;

use App\Shared\Audit\Auditable;
use Database\Factories\ShiftFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $start_time "HH:MM:SS"
 * @property string $end_time "HH:MM:SS"
 * @property int $break_minutes
 * @property int $grace_minutes
 * @property list<int> $work_days
 * @property bool $is_default
 * @property bool $is_flexible
 * @property string|null $core_start
 * @property string|null $core_end
 * @property int|null $required_minutes
 */
#[Fillable(['name', 'start_time', 'end_time', 'break_minutes', 'grace_minutes', 'work_days', 'is_default', 'is_flexible', 'core_start', 'core_end', 'required_minutes'])]
#[UseFactory(ShiftFactory::class)]
class Shift extends Model
{
    use Auditable;

    /** @use HasFactory<ShiftFactory> */
    use HasFactory;

    public const WEEKDAYS = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'work_days' => 'array',
            'is_default' => 'boolean',
            'is_flexible' => 'boolean',
            'required_minutes' => 'integer',
            'break_minutes' => 'integer',
            'grace_minutes' => 'integer',
        ];
    }

    /**
     * @return HasMany<EmployeeShift, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(EmployeeShift::class);
    }

    public function crossesMidnight(): bool
    {
        return $this->end_time <= $this->start_time;
    }

    public function isWorkDay(Carbon $date): bool
    {
        return in_array($date->isoWeekday(), array_map('intval', $this->work_days), true);
    }

    public function startsAt(Carbon $date): Carbon
    {
        return $date->copy()->setTimeFromTimeString($this->start_time);
    }

    public function endsAt(Carbon $date): Carbon
    {
        $end = $date->copy()->setTimeFromTimeString($this->end_time);

        return $this->crossesMidnight() ? $end->addDay() : $end;
    }

    /**
     * Paid minutes of a complete shift (span minus break).
     */
    public function scheduledMinutes(): int
    {
        if ($this->is_flexible && $this->required_minutes) {
            return $this->required_minutes;
        }

        $today = Carbon::today();

        return (int) $this->startsAt($today)->diffInMinutes($this->endsAt($today)) - $this->break_minutes;
    }

    /**
     * A copy of the shift where the given date is (or isn't) a work day, for
     * roster overrides. Never saved.
     */
    public function withWorkDay(Carbon $date, bool $works): self
    {
        $copy = clone $this;
        $days = array_map('intval', $this->work_days);
        $copy->work_days = $works ? array_values(array_unique([...$days, $date->isoWeekday()])) : array_values(array_diff($days, [$date->isoWeekday()]));

        return $copy;
    }

    public function label(): string
    {
        return sprintf('%s (%s–%s)', $this->name, substr($this->start_time, 0, 5), substr($this->end_time, 0, 5));
    }

    public function workDaysLabel(): string
    {
        return collect($this->work_days)->sort()->map(fn ($day) => self::WEEKDAYS[(int) $day])->implode(', ');
    }
}
