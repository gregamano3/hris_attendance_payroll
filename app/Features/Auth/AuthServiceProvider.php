<?php

namespace App\Features\Auth;

use App\Models\User;
use App\Shared\Authorization\Role;
use App\Shared\Providers\FeatureServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends FeatureServiceProvider
{
    protected function bootFeature(): void
    {
        // Administrators are allowed to do everything.
        Gate::before(fn (User $user) => $user->hasRole(Role::Admin->value) ? true : null);
    }
}
