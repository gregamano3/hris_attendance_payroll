<?php

use App\Features\Payroll\CreatePayrollRun\CreatePayrollRunController;
use App\Features\Payroll\ExportBankFile\ExportBankFileController;
use App\Features\Payroll\ExportRegister\ExportRegisterController;
use App\Features\Payroll\ListPayrollRuns\ListPayrollRunsController;
use App\Features\Payroll\ManageAdjustments\AdjustmentsController;
use App\Features\Payroll\ManageFinalPay\FinalPayController;
use App\Features\Payroll\ManageLoans\LoansController;
use App\Features\Payroll\ManageRecurringEarnings\RecurringEarningsController;
use App\Features\Payroll\ManageStatutoryRates\StatutoryRatesController;
use App\Features\Payroll\ProcessPayrollRun\ProcessPayrollRunController;
use App\Features\Payroll\Reports\ReportsController;
use App\Features\Payroll\ShowMyPayslips\ShowMyPayslipsController;
use App\Features\Payroll\ShowPayrollRun\ShowPayrollRunController;
use App\Features\Payroll\ShowPayslip\ShowPayslipController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('payroll')->name('payroll.')->group(function () {
    Route::get('my-payslips', ShowMyPayslipsController::class)->middleware('can:payslips.view-own')->name('mine');

    // Access is checked inside: payroll staff or the employee owning a finalized payslip.
    Route::get('payslips/{payslip}', [ShowPayslipController::class, 'show'])->name('payslips.show');
    Route::get('payslips/{payslip}/pdf', [ShowPayslipController::class, 'pdf'])->name('payslips.pdf');
    Route::get('reports/2316/{year}/{employee}', [ReportsController::class, 'certificate'])->whereNumber('year')->name('reports.2316');

    Route::middleware('can:payroll.manage')->group(function () {
        Route::get('runs/create', [CreatePayrollRunController::class, 'create'])->name('runs.create');
        Route::post('runs', [CreatePayrollRunController::class, 'store'])->name('runs.store');
        Route::post('runs/{run}/compute', [ProcessPayrollRunController::class, 'compute'])->name('runs.compute');
        Route::delete('runs/{run}', [ProcessPayrollRunController::class, 'destroy'])->name('runs.destroy');
        Route::post('runs/{run}/adjustments', [AdjustmentsController::class, 'store'])->name('runs.adjustments.store');
        Route::delete('runs/{run}/adjustments/{adjustment}', [AdjustmentsController::class, 'destroy'])->name('runs.adjustments.destroy');

        Route::post('allowances', [RecurringEarningsController::class, 'store'])->name('allowances.store');
        Route::patch('allowances/{recurringEarning}', [RecurringEarningsController::class, 'update'])->name('allowances.update');
        Route::delete('allowances/{recurringEarning}', [RecurringEarningsController::class, 'destroy'])->name('allowances.destroy');
        Route::post('loans', [LoansController::class, 'store'])->name('loans.store');
        Route::patch('loans/{loan}/cancel', [LoansController::class, 'cancel'])->name('loans.cancel');

        Route::post('final-pay', [FinalPayController::class, 'store'])->name('final-pay.store');
        Route::post('final-pay/{finalPay}/compute', [FinalPayController::class, 'compute'])->name('final-pay.compute');
        Route::delete('final-pay/{finalPay}', [FinalPayController::class, 'destroy'])->name('final-pay.destroy');
        Route::post('final-pay/{finalPay}/adjustments', [FinalPayController::class, 'storeAdjustment'])->name('final-pay.adjustments.store');
        Route::delete('final-pay/{finalPay}/adjustments/{adjustment}', [FinalPayController::class, 'destroyAdjustment'])->name('final-pay.adjustments.destroy');
    });

    Route::post('final-pay/{finalPay}/finalize', [FinalPayController::class, 'finalize'])
        ->middleware('can:payroll.finalize')
        ->name('final-pay.finalize');

    Route::post('runs/{run}/finalize', [ProcessPayrollRunController::class, 'finalize'])
        ->middleware('can:payroll.finalize')
        ->name('runs.finalize');

    Route::middleware('can:payroll.view')->group(function () {
        Route::get('runs', ListPayrollRunsController::class)->name('runs.index');
        Route::get('runs/{run}', ShowPayrollRunController::class)->name('runs.show');
        Route::get('runs/{run}/status', [ProcessPayrollRunController::class, 'status'])->name('runs.status');
        Route::get('runs/{run}/register.csv', ExportRegisterController::class)->name('runs.register');
        Route::get('runs/{run}/bank.{format}', ExportBankFileController::class)->whereIn('format', ['csv', 'txt'])->name('runs.bank');
        Route::get('allowances', [RecurringEarningsController::class, 'index'])->name('allowances.index');
        Route::get('loans', [LoansController::class, 'index'])->name('loans.index');
        Route::get('loans/{loan}', [LoansController::class, 'show'])->name('loans.show');
        Route::get('final-pay', [FinalPayController::class, 'index'])->name('final-pay.index');
        Route::get('final-pay/{finalPay}', [FinalPayController::class, 'show'])->name('final-pay.show');
        Route::get('final-pay/{finalPay}/pdf', [FinalPayController::class, 'pdf'])->name('final-pay.pdf');
        Route::get('reports', [ReportsController::class, 'index'])->name('reports.index');
        Route::get('reports/alphalist.csv', [ReportsController::class, 'alphalist'])->name('reports.alphalist');
        Route::get('reports/{agency}.csv', [ReportsController::class, 'contributions'])->name('reports.contributions');
    });

    Route::middleware('can:settings.manage')->group(function () {
        Route::get('statutory-rates', [StatutoryRatesController::class, 'index'])->name('statutory.index');
        Route::post('statutory-rates', [StatutoryRatesController::class, 'storeRate'])->name('statutory.store');
        Route::post('statutory-rates/tax', [StatutoryRatesController::class, 'storeTaxTable'])->name('statutory.tax.store');
    });
});
