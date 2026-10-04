<?php

namespace App\Features\Employees\Forms;

use App\Features\Employees\Enums\CivilStatus;
use App\Features\Employees\Enums\EmploymentStatus;
use App\Features\Employees\Enums\EmploymentType;
use App\Features\Employees\Enums\Gender;
use App\Features\Employees\Enums\RateType;
use App\Features\Employees\Models\Employee;
use App\Features\Employees\Queries\EmployeeOptions;
use App\Models\User;

/**
 * Builds the view data needed by the employee form.
 */
class EmployeeFormData
{
    public function __construct(private EmployeeOptions $options) {}

    /**
     * @return array<string, mixed>
     */
    public function for(Employee $employee): array
    {
        return [
            'employee' => $employee,
            'departments' => $this->options->departments(),
            'positions' => $this->options->positions(),
            'users' => User::query()
                ->whereNotIn('id', Employee::query()->whereNotNull('user_id')->whereKeyNot($employee->getKey() ?? 0)->select('user_id'))
                ->orderBy('name')
                ->pluck('name', 'id'),
            'employmentTypes' => EmploymentType::options(),
            'statuses' => EmploymentStatus::options(),
            'rateTypes' => RateType::options(),
            'genders' => Gender::options(),
            'civilStatuses' => CivilStatus::options(),
        ];
    }
}
