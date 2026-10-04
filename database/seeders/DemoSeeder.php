<?php

namespace Database\Seeders;

use App\Features\Attendance\Compute\ComputeAttendanceDay;
use App\Features\Attendance\Enums\LeaveStatus;
use App\Features\Attendance\Enums\TimeLogSource;
use App\Features\Attendance\Enums\TimeLogType;
use App\Features\Attendance\Models\OvertimeRequest;
use App\Features\Attendance\Models\TimeLog;
use App\Features\Employees\Enums\EmploymentType;
use App\Features\Employees\Models\Department;
use App\Features\Employees\Models\Employee;
use App\Features\Employees\Models\Position;
use App\Models\User;
use App\Shared\Authorization\Role;
use Database\Factories\EmployeeFactory;
use Illuminate\Database\Seeder;

/**
 * Sample organisation for local development, demos and end-to-end tests.
 * Every demo account uses the password "password".
 */
class DemoSeeder extends Seeder
{
    /**
     * @var array<string, array{name: string, positions: list<string>}>
     */
    private const DEPARTMENTS = [
        'HR' => ['name' => 'Human Resources', 'positions' => ['HR Manager', 'HR Associate']],
        'FIN' => ['name' => 'Finance', 'positions' => ['Payroll Officer', 'Accountant']],
        'OPS' => ['name' => 'Operations', 'positions' => ['Operations Supervisor', 'Production Staff']],
        'IT' => ['name' => 'Information Technology', 'positions' => ['Software Engineer', 'IT Support']],
    ];

    public function run(): void
    {
        $positions = [];

        foreach (self::DEPARTMENTS as $code => $data) {
            $department = Department::query()->firstOrCreate(['code' => $code], ['name' => $data['name']]);

            foreach ($data['positions'] as $title) {
                $positions[$title] = Position::query()->firstOrCreate(
                    ['department_id' => $department->id, 'title' => $title],
                );
            }
        }

        // One account per role, each linked to an employee record.
        $accounts = [
            ['hr@example.com', 'Maria Santos', Role::Hr, 'HR Manager', 55_000],
            ['payroll@example.com', 'Jose Reyes', Role::Payroll, 'Payroll Officer', 45_000],
            ['employee@example.com', 'Juan Dela Cruz', Role::Employee, 'Production Staff', 22_000],
        ];

        foreach ($accounts as [$email, $name, $role, $positionTitle, $salary]) {
            $user = User::query()->firstOrCreate(
                ['email' => $email],
                ['name' => $name, 'password' => 'password', 'email_verified_at' => now()],
            );
            $user->syncRoles([$role->value]);

            [$first, $last] = explode(' ', $name, 2);
            $position = $positions[$positionTitle];

            if (! Employee::query()->where('user_id', $user->id)->exists()) {
                Employee::factory()->monthly($salary)->create([
                    'user_id' => $user->id,
                    'first_name' => $first,
                    'last_name' => $last,
                    'email' => $email,
                    'department_id' => $position->department_id,
                    'position_id' => $position->id,
                ]);
            }
        }

        if (Employee::query()->count() < 20) {
            $this->seedEmployees($positions);
        }

        $this->seedTimeLogs();
    }

    /**
     * @param  array<string, Position>  $positions
     */
    private function seedEmployees(array $positions): void
    {
        foreach (range(1, 20) as $i) {
            $position = fake()->randomElement($positions);

            Employee::factory()
                ->state([
                    'department_id' => $position->department_id,
                    'position_id' => $position->id,
                    'employment_type' => fake()->randomElement(EmploymentType::cases()),
                ])
                ->when(
                    $i % 4 === 0,
                    fn (EmployeeFactory $factory) => $factory->daily(fake()->randomElement([610, 645, 695])),
                    fn (EmployeeFactory $factory) => $factory->monthly(fake()->numberBetween(20, 60) * 1000),
                )
                ->create();
        }
    }

    /**
     * Punches for the last three weeks so DTRs and payroll have data.
     */
    private function seedTimeLogs(): void
    {
        if (TimeLog::query()->exists()) {
            return;
        }

        $from = today()->subDays(21);
        $compute = app(ComputeAttendanceDay::class);

        TimeLog::withoutEvents(function () use ($from) {
            foreach (Employee::query()->active()->pluck('id') as $employeeId) {
                for ($date = $from->copy(); $date->lt(today()); $date->addDay()) {
                    if ($date->isWeekend() || fake()->boolean(4)) {
                        continue; // rest day or the occasional absence
                    }

                    $in = $date->copy()->setTime(7, 45)->addMinutes(fake()->biasedNumberBetween(0, 45, 'Faker\Provider\Biased::linearLow'));
                    $extra = fake()->boolean(25) ? fake()->numberBetween(30, 180) : fake()->numberBetween(0, 10);
                    $out = $date->copy()->setTime(17, 0)->addMinutes($extra);

                    if ($extra >= 30) {
                        OvertimeRequest::query()->insert([
                            'employee_id' => $employeeId, 'date' => $date->toDateString(), 'minutes' => $extra,
                            'reason' => 'Month-end workload', 'status' => LeaveStatus::Approved->value,
                            'reviewed_at' => now(), 'created_at' => now(), 'updated_at' => now(),
                        ]);
                    }

                    TimeLog::query()->insert([
                        ['employee_id' => $employeeId, 'logged_at' => $in, 'type' => TimeLogType::In->value, 'source' => TimeLogSource::Import->value, 'created_at' => now(), 'updated_at' => now()],
                        ['employee_id' => $employeeId, 'logged_at' => $out, 'type' => TimeLogType::Out->value, 'source' => TimeLogSource::Import->value, 'created_at' => now(), 'updated_at' => now()],
                    ]);
                }
            }
        });

        foreach (Employee::query()->active()->pluck('id') as $employeeId) {
            $compute->handleRange($employeeId, $from, today());
        }
    }
}
