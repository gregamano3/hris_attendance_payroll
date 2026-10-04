<?php

namespace App\Features\Users\CreateUser;

use App\Models\User;
use App\Shared\Authorization\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CreateUserController
{
    public function create(): View
    {
        return view('users::create', ['user' => new User(['is_active' => true]), 'roles' => Role::options()]);
    }

    public function store(CreateUserRequest $request): RedirectResponse
    {
        $user = User::query()->create([
            ...$request->safe()->only(['name', 'email', 'password']),
            'is_active' => $request->boolean('is_active'),
        ]);

        $user->syncRoles([$request->validated('role')]);

        return redirect()->route('users.index')->with('success', "User {$user->name} created.");
    }
}
