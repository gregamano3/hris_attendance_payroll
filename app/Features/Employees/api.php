<?php

use App\Features\Employees\Api\EmployeesApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('v1.')->middleware(['api.v1', 'abilities:employees:read', 'can:employees.view'])->group(function () {
    Route::get('employees', [EmployeesApiController::class, 'index'])->name('employees.index');
    Route::get('employees/{employee}', [EmployeesApiController::class, 'show'])->whereNumber('employee')->name('employees.show');
});
