<?php

namespace App\Features\Api\Me;

use App\Features\Employees\Api\EmployeeResource;
use App\Features\Employees\Queries\EmployeeDirectory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController
{
    public function __invoke(Request $request, EmployeeDirectory $directory): JsonResponse
    {
        $user = $request->user();
        abort_if($user === null, 401);
        $employee = $directory->forUser($user)?->load(['department', 'position', 'branch']);

        return response()->json(['data' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->primaryRole()?->value,
            'permissions' => $user->getAllPermissions()->pluck('name')->sort()->values(),
            'token_abilities' => $user->currentAccessToken()->abilities ?? [],
            'employee' => $employee ? new EmployeeResource($employee) : null,
        ]]);
    }
}
