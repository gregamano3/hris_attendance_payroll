<?php

use App\Features\Employees\ArchiveEmployee\ArchiveEmployeeController;
use App\Features\Employees\CreateEmployee\CreateEmployeeController;
use App\Features\Employees\ListEmployees\ListEmployeesController;
use App\Features\Employees\ManageDepartments\DepartmentsController;
use App\Features\Employees\ManagePositions\PositionsController;
use App\Features\Employees\ShowEmployee\ShowEmployeeController;
use App\Features\Employees\ShowMyProfile\ShowMyProfileController;
use App\Features\Employees\UpdateEmployee\UpdateEmployeeController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('my-profile', ShowMyProfileController::class)->name('my-profile');

    Route::prefix('employees')->name('employees.')->group(function () {
        Route::middleware('can:employees.manage')->group(function () {
            Route::get('create', [CreateEmployeeController::class, 'create'])->name('create');
            Route::post('/', [CreateEmployeeController::class, 'store'])->name('store');
            Route::get('{employee}/edit', [UpdateEmployeeController::class, 'edit'])->name('edit');
            Route::put('{employee}', [UpdateEmployeeController::class, 'update'])->name('update');
            Route::delete('{employee}', ArchiveEmployeeController::class)->name('archive');
        });

        Route::middleware('can:employees.view')->group(function () {
            Route::get('/', ListEmployeesController::class)->name('index');
            Route::get('{employee}', ShowEmployeeController::class)->name('show');
        });
    });

    Route::middleware('can:employees.manage')->group(function () {
        Route::resource('departments', DepartmentsController::class)->except('show');
        Route::resource('positions', PositionsController::class)->except('show');
    });
});
