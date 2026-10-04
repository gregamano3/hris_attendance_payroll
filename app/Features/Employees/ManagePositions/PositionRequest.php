<?php

namespace App\Features\Employees\ManagePositions;

use App\Features\Employees\Models\Position;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PositionRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Position|null $position */
        $position = $this->route('position');

        return [
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')],
            'title' => [
                'required', 'string', 'max:255',
                Rule::unique('positions')
                    ->where('department_id', $this->input('department_id'))
                    ->ignore($position),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
