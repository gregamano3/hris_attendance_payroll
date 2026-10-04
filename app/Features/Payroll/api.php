<?php

use App\Features\Payroll\Api\PayslipsApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('v1.')->middleware(['api.v1', 'abilities:payslips:read'])->group(function () {
    Route::get('payslips', [PayslipsApiController::class, 'index'])->name('payslips.index');
    Route::get('payslips/{payslip}', [PayslipsApiController::class, 'show'])->whereNumber('payslip')->name('payslips.show');
});
