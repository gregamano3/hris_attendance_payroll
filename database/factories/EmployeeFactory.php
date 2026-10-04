<?php

namespace Database\Factories;

use App\Features\Employees\Enums\CivilStatus;
use App\Features\Employees\Enums\EmploymentStatus;
use App\Features\Employees\Enums\EmploymentType;
use App\Features\Employees\Enums\Gender;
use App\Features\Employees\Enums\RateType;
use App\Features\Employees\Models\Department;
use App\Features\Employees\Models\Employee;
use App\Features\Employees\Models\Position;
use App\Models\User;
use App\Shared\Money\Money;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        $gender = fake()->randomElement(Gender::cases());

        return [
            'employee_no' => 'EMP-'.fake()->unique()->numerify('#####'),
            'first_name' => fake()->firstName($gender->value),
            'middle_name' => fake()->lastName(),
            'last_name' => fake()->lastName(),
            'birth_date' => fake()->dateTimeBetween('-55 years', '-20 years'),
            'gender' => $gender,
            'civil_status' => fake()->randomElement(CivilStatus::cases()),
            'email' => fake()->unique()->safeEmail(),
            'mobile' => '09'.fake()->numerify('#########'),
            'address' => fake()->address(),
            'department_id' => Department::factory(),
            'position_id' => fn (array $attributes) => Position::factory()->state(['department_id' => $attributes['department_id']]),
            'employment_type' => EmploymentType::Regular,
            'status' => EmploymentStatus::Active,
            'hired_at' => fake()->dateTimeBetween('-8 years', '-3 months'),
            'rate_type' => RateType::Monthly,
            'basic_rate' => Money::ofPesos(fake()->numberBetween(18, 80) * 1000),
            'sss_no' => fake()->unique()->numerify('##########'),
            'philhealth_no' => fake()->unique()->numerify('############'),
            'pagibig_no' => fake()->unique()->numerify('############'),
            'tin' => fake()->unique()->numerify('#########'),
        ];
    }

    public function daily(int|string $pesos = 645): static
    {
        return $this->state(fn () => [
            'rate_type' => RateType::Daily,
            'basic_rate' => Money::ofPesos($pesos),
        ]);
    }

    public function monthly(int|string $pesos): static
    {
        return $this->state(fn () => [
            'rate_type' => RateType::Monthly,
            'basic_rate' => Money::ofPesos($pesos),
        ]);
    }

    public function forUser(?User $user = null): static
    {
        return $this->state(fn () => ['user_id' => $user !== null ? $user->id : User::factory()]);
    }

    public function separated(): static
    {
        return $this->state(fn () => [
            'status' => EmploymentStatus::Resigned,
            'separated_at' => now()->subMonth(),
        ]);
    }
}
