<?php

namespace Database\Factories;

use App\Features\Employees\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Human Resources', 'Finance', 'Operations', 'Information Technology', 'Sales',
            'Marketing', 'Customer Service', 'Logistics', 'Production', 'Administration',
        ]).' '.fake()->unique()->numberBetween(1, 9999);

        return [
            'code' => strtoupper(fake()->unique()->lexify('???')).fake()->numberBetween(10, 99),
            'name' => $name,
            'description' => null,
        ];
    }
}
