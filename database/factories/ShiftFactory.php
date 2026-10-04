<?php

namespace Database\Factories;

use App\Features\Attendance\Models\Shift;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Shift>
 */
class ShiftFactory extends Factory
{
    protected $model = Shift::class;

    public function definition(): array
    {
        return [
            'name' => 'Day shift '.fake()->unique()->numberBetween(1, 99999),
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'break_minutes' => 60,
            'grace_minutes' => 5,
            'work_days' => [1, 2, 3, 4, 5],
            'is_default' => false,
        ];
    }

    public function night(): static
    {
        return $this->state(fn () => [
            'name' => 'Night shift '.fake()->unique()->numberBetween(1, 99999),
            'start_time' => '22:00:00',
            'end_time' => '07:00:00',
        ]);
    }

    public function default(): static
    {
        return $this->state(fn () => ['is_default' => true]);
    }
}
