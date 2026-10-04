<?php

namespace App\Features\Employees\ShowMyProfile;

use App\Features\Employees\Queries\EmployeeDirectory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShowMyProfileController
{
    public function __invoke(Request $request, EmployeeDirectory $directory): View
    {
        $employee = $directory->forUser($request->user());
        $employee?->load(['department', 'position']);

        return view('employees::my-profile', compact('employee'));
    }
}
