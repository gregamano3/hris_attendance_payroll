<?php

use App\Features\Employees\ArchiveEmployee\ArchiveEmployeeController;
use App\Features\Employees\CreateEmployee\CreateEmployeeController;
use App\Features\Employees\ImportEmployees\ImportEmployeesController;
use App\Features\Employees\ListEmployees\ListEmployeesController;
use App\Features\Employees\ManageCompensation\CompensationController;
use App\Features\Employees\ManageDepartments\DepartmentsController;
use App\Features\Employees\ManageDocuments\DocumentsController;
use App\Features\Employees\ManagePositions\PositionsController;
use App\Features\Employees\ShowEmployee\ShowEmployeeController;
use App\Features\Employees\ShowMyProfile\ShowMyProfileController;
use App\Features\Employees\UpdateEmployee\UpdateEmployeeController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('my-profile', ShowMyProfileController::class)->name('my-profile');

    // Access is checked inside: staff with employees.view or the document's owner.
    Route::get('documents/{document}', [DocumentsController::class, 'download'])->name('documents.download');

    Route::prefix('employees')->name('employees.')->group(function () {
        Route::middleware('can:employees.manage')->group(function () {
            Route::get('create', [CreateEmployeeController::class, 'create'])->name('create');
            Route::get('import', [ImportEmployeesController::class, 'create'])->name('import.create');
            Route::post('import', [ImportEmployeesController::class, 'store'])->name('import.store');
            Route::get('import/template.csv', [ImportEmployeesController::class, 'template'])->name('import.template');
            Route::post('{employee}/documents', [DocumentsController::class, 'store'])->name('documents.store');
            Route::get('{employee}/compensation', [CompensationController::class, 'index'])->name('compensation.index');
            Route::post('{employee}/compensation', [CompensationController::class, 'store'])->name('compensation.store');
            Route::delete('{employee}/compensation/{change}', [CompensationController::class, 'destroy'])->name('compensation.destroy');
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

    Route::delete('documents/{document}', [DocumentsController::class, 'destroy'])->middleware('can:employees.manage')->name('documents.destroy');

    Route::middleware('can:employees.manage')->group(function () {
        Route::resource('departments', DepartmentsController::class)->except('show');
        Route::resource('positions', PositionsController::class)->except('show');
    });
});
