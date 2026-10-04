<?php

namespace App\Features\Users\UpdateUser;

use App\Models\User;
use App\Shared\Authorization\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class UpdateUserRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var User $user */
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'role' => ['required', Rule::enum(Role::class)],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * Prevent administrators from locking themselves out.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (! $this->route('user')?->is($this->user())) {
                    return;
                }

                if (! $this->boolean('is_active')) {
                    $validator->errors()->add('is_active', 'You cannot deactivate your own account.');
                }

                if ($this->input('role') !== Role::Admin->value && $this->user()?->hasRole(Role::Admin->value)) {
                    $validator->errors()->add('role', 'You cannot remove your own administrator role.');
                }
            },
        ];
    }
}
