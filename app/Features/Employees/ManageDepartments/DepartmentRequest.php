<?php

namespace App\Features\Employees\ManageDepartments;

use App\Features\Employees\Models\Department;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DepartmentRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['code' => strtoupper(trim((string) $this->input('code')))]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Department|null $department */
        $department = $this->route('department');

        return [
            'code' => ['required', 'string', 'max:20', 'alpha_dash', Rule::unique('departments')->ignore($department)],
            'name' => ['required', 'string', 'max:255', Rule::unique('departments')->ignore($department)],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
