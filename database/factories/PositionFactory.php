<?php

namespace Database\Factories;

use App\Features\Employees\Models\Department;
use App\Features\Employees\Models\Position;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Position>
 */
class PositionFactory extends Factory
{
    protected $model = Position::class;

    public function definition(): array
    {
        return [
            'department_id' => Department::factory(),
            'title' => fake()->unique()->jobTitle(),
            'description' => null,
        ];
    }
}
