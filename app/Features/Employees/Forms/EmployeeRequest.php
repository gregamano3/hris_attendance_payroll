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
        $this->merge(self::normalize($this->all()));
    }

    /**
     * Normalises user input (government IDs and bank account to digits, amounts without commas).
     * Shared with the CSV import.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function normalize(array $input): array
    {
        $normalized = [];

        foreach (GovernmentId::cases() as $id) {
            $normalized[$id->value] = GovernmentId::normalize(isset($input[$id->value]) ? (string) $input[$id->value] : null);
        }

        $normalized['basic_rate'] = str_replace(',', '', (string) ($input['basic_rate'] ?? ''));
        $normalized['bank_account_no'] = preg_replace('/[\s-]/', '', (string) ($input['bank_account_no'] ?? '')) ?: null;

        return $normalized;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return self::rulesFor($this->employee(), (string) $this->input('status'));
    }

    /**
     * @return array<string, mixed>
     */
    public static function rulesFor(?Employee $employee, ?string $status): array
    {
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
            'branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')],
            'cost_center_id' => ['nullable', 'integer', Rule::exists('cost_centers', 'id')],
            'supervisor_id' => ['nullable', 'integer', Rule::exists('employees', 'id'), Rule::notIn(array_filter([$employee?->id]))],
            'employment_type' => ['required', Rule::enum(EmploymentType::class)],
            'status' => ['required', Rule::enum(EmploymentStatus::class)],
            'hired_at' => ['required', 'date'],
            'regularized_at' => ['nullable', 'date', 'after_or_equal:hired_at'],
            'separated_at' => [
                Rule::requiredIf(fn () => EmploymentStatus::tryFrom((string) $status)?->isSeparated() ?? false),
                'nullable', 'date', 'after_or_equal:hired_at',
            ],
            'rate_type' => ['required', Rule::enum(RateType::class)],
            'pay_frequency' => ['nullable', Rule::in(['semi_monthly', 'monthly', 'weekly'])],
            'basic_rate' => ['required', 'numeric', 'gt:0', 'max:99999999'],
            'is_minimum_wage_earner' => ['boolean'],
            'bank_name' => ['nullable', 'required_with:bank_account_no', 'string', 'max:100'],
            'bank_account_name' => ['nullable', 'string', 'max:255'],
            'bank_account_no' => ['nullable', 'regex:/^\d{6,20}$/'],
        ];

        foreach (GovernmentId::cases() as $id) {
            $rules[$id->value] = [
                'nullable',
                function (string $attribute, mixed $value, \Closure $fail) use ($id) {
                    if (! in_array(strlen((string) $value), $id->lengths(), true)) {
                        $fail("The {$id->label()} must have ".implode(' or ', $id->lengths()).' digits.');
                    }
                },
                // Encrypted column: uniqueness is checked on its blind index.
                function (string $attribute, mixed $value, \Closure $fail) use ($id, $employee) {
                    $existing = Employee::findByGovernmentId($id, (string) $value);

                    if ($existing !== null && ! $existing->is($employee)) {
                        $fail("The {$id->label()} has already been taken.");
                    }
                },
            ];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return self::attributeNames();
    }

    /**
     * @return array<string, string>
     */
    public static function attributeNames(): array
    {
        $attributes = [
            'user_id' => 'linked user',
            'department_id' => 'department',
            'position_id' => 'position',
            'branch_id' => 'branch',
            'cost_center_id' => 'cost center',
            'supervisor_id' => 'supervisor',
            'hired_at' => 'hire date',
            'regularized_at' => 'regularization date',
            'separated_at' => 'separation date',
            'bank_account_no' => 'bank account number',
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
