<?php

namespace App\Features\Employees\Forms;

use App\Features\Employees\Enums\CivilStatus;
use App\Features\Employees\Enums\EmploymentStatus;
use App\Features\Employees\Enums\EmploymentType;
use App\Features\Employees\Enums\Gender;
use App\Features\Employees\Enums\GovernmentId;
use App\Features\Employees\Enums\RateType;
use App\Features\Employees\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation shared by the CreateEmployee and UpdateEmployee slices.
 */
class EmployeeRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $normalized = [];

        foreach (GovernmentId::cases() as $id) {
            $normalized[$id->value] = GovernmentId::normalize($this->input($id->value));
        }

        $normalized['basic_rate'] = str_replace(',', '', (string) $this->input('basic_rate'));

        $this->merge($normalized);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $employee = $this->employee();

        $rules = [
            'employee_no' => ['required', 'string', 'max:30', Rule::unique('employees')->ignore($employee)],
            'user_id' => ['nullable', 'integer', Rule::exists('users', 'id'), Rule::unique('employees')->ignore($employee)],
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'suffix' => ['nullable', 'string', 'max:10'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', Rule::enum(Gender::class)],
            'civil_status' => ['nullable', Rule::enum(CivilStatus::class)],
            'email' => ['nullable', 'email', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:1000'],
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')],
            'position_id' => ['nullable', 'integer', Rule::exists('positions', 'id')],
            'employment_type' => ['required', Rule::enum(EmploymentType::class)],
            'status' => ['required', Rule::enum(EmploymentStatus::class)],
            'hired_at' => ['required', 'date'],
            'regularized_at' => ['nullable', 'date', 'after_or_equal:hired_at'],
            'separated_at' => [
                Rule::requiredIf(fn () => EmploymentStatus::tryFrom((string) $this->input('status'))?->isSeparated() ?? false),
                'nullable', 'date', 'after_or_equal:hired_at',
            ],
            'rate_type' => ['required', Rule::enum(RateType::class)],
            'basic_rate' => ['required', 'numeric', 'gt:0', 'max:99999999'],
            'is_minimum_wage_earner' => ['boolean'],
        ];

        foreach (GovernmentId::cases() as $id) {
            $rules[$id->value] = [
                'nullable',
                function (string $attribute, mixed $value, \Closure $fail) use ($id) {
                    if (! in_array(strlen((string) $value), $id->lengths(), true)) {
                        $fail("The {$id->label()} must have ".implode(' or ', $id->lengths()).' digits.');
                    }
                },
                Rule::unique('employees', $id->value)->ignore($employee),
            ];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $attributes = [
            'user_id' => 'linked user',
            'department_id' => 'department',
            'position_id' => 'position',
            'hired_at' => 'hire date',
            'regularized_at' => 'regularization date',
            'separated_at' => 'separation date',
        ];

        foreach (GovernmentId::cases() as $id) {
            $attributes[$id->value] = $id->label();
        }

        return $attributes;
    }

    private function employee(): ?Employee
    {
        $employee = $this->route('employee');

        return $employee instanceof Employee ? $employee : null;
    }
}
