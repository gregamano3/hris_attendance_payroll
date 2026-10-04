<?php

namespace App\Features\Users\UpdateUser;

use App\Models\User;
use App\Shared\Authorization\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UpdateUserController
{
    public function edit(User $user): View
    {
        return view('users::edit', ['user' => $user, 'roles' => Role::options()]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $user->fill([
            ...$request->safe()->only(['name', 'email']),
            'is_active' => $request->boolean('is_active'),
        ]);

        if ($request->filled('password')) {
            $user->password = $request->validated('password');
        }

        $user->save();
        $user->syncRoles([$request->validated('role')]);

        return redirect()->route('users.index')->with('success', "User {$user->name} updated.");
    }
}
