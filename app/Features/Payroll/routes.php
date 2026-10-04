<?php

use App\Features\Payroll\CreatePayrollRun\CreatePayrollRunController;
use App\Features\Payroll\ExportRegister\ExportRegisterController;
use App\Features\Payroll\ListPayrollRuns\ListPayrollRunsController;
use App\Features\Payroll\ManageAdjustments\AdjustmentsController;
use App\Features\Payroll\ManageStatutoryRates\StatutoryRatesController;
use App\Features\Payroll\ProcessPayrollRun\ProcessPayrollRunController;
use App\Features\Payroll\ShowMyPayslips\ShowMyPayslipsController;
use App\Features\Payroll\ShowPayrollRun\ShowPayrollRunController;
use App\Features\Payroll\ShowPayslip\ShowPayslipController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('payroll')->name('payroll.')->group(function () {
    Route::get('my-payslips', ShowMyPayslipsController::class)->middleware('can:payslips.view-own')->name('mine');

    // Access is checked inside: payroll staff or the employee owning a finalized payslip.
    Route::get('payslips/{payslip}', [ShowPayslipController::class, 'show'])->name('payslips.show');
    Route::get('payslips/{payslip}/pdf', [ShowPayslipController::class, 'pdf'])->name('payslips.pdf');

    Route::middleware('can:payroll.manage')->group(function () {
        Route::get('runs/create', [CreatePayrollRunController::class, 'create'])->name('runs.create');
        Route::post('runs', [CreatePayrollRunController::class, 'store'])->name('runs.store');
        Route::post('runs/{run}/compute', [ProcessPayrollRunController::class, 'compute'])->name('runs.compute');
        Route::delete('runs/{run}', [ProcessPayrollRunController::class, 'destroy'])->name('runs.destroy');
        Route::post('runs/{run}/adjustments', [AdjustmentsController::class, 'store'])->name('runs.adjustments.store');
        Route::delete('runs/{run}/adjustments/{adjustment}', [AdjustmentsController::class, 'destroy'])->name('runs.adjustments.destroy');
    });

    Route::post('runs/{run}/finalize', [ProcessPayrollRunController::class, 'finalize'])
        ->middleware('can:payroll.finalize')
        ->name('runs.finalize');

    Route::middleware('can:payroll.view')->group(function () {
        Route::get('runs', ListPayrollRunsController::class)->name('runs.index');
        Route::get('runs/{run}', ShowPayrollRunController::class)->name('runs.show');
        Route::get('runs/{run}/register.csv', ExportRegisterController::class)->name('runs.register');
    });

    Route::middleware('can:settings.manage')->group(function () {
        Route::get('statutory-rates', [StatutoryRatesController::class, 'index'])->name('statutory.index');
        Route::post('statutory-rates', [StatutoryRatesController::class, 'storeRate'])->name('statutory.store');
        Route::post('statutory-rates/tax', [StatutoryRatesController::class, 'storeTaxTable'])->name('statutory.tax.store');
    });
});
