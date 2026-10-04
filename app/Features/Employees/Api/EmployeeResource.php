<?php

namespace App\Features\Employees\Api;

use App\Features\Employees\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Directory view of an employee. Government IDs, bank details, pay and
 * personal data (birth date, address, mobile) are deliberately left out.
 *
 * @mixin Employee
 */
class EmployeeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_no' => $this->employee_no,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'suffix' => $this->suffix,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'department' => $this->whenLoaded('department', fn () => $this->department ? ['id' => $this->department->id, 'name' => $this->department->name] : null),
            'position' => $this->whenLoaded('position', fn () => $this->position ? ['id' => $this->position->id, 'title' => $this->position->title] : null),
            'branch' => $this->whenLoaded('branch', fn () => $this->branch ? ['id' => $this->branch->id, 'name' => $this->branch->name] : null),
            'supervisor_id' => $this->supervisor_id,
            'employment_type' => $this->employment_type->value,
            'status' => $this->status->value,
            'hired_at' => $this->hired_at->toDateString(),
            'regularized_at' => $this->regularized_at?->toDateString(),
            'separated_at' => $this->separated_at?->toDateString(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
