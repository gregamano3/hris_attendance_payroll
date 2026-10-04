<?php

use App\Features\Attendance\Api\AttendanceApiController;
use App\Features\Attendance\Api\LeavesApiController;
use App\Features\Attendance\DeviceApi\AuthenticateDevice;
use App\Features\Attendance\DeviceApi\DevicePunchesController;
use Illuminate\Support\Facades\Route;

Route::post('attendance/punches', DevicePunchesController::class)
    ->middleware([AuthenticateDevice::class, 'throttle:120,1'])
    ->name('attendance.punches');

Route::prefix('v1')->name('v1.')->middleware('api.v1')->group(function () {
    Route::middleware('abilities:attendance:read')->group(function () {
        Route::get('attendance/days', [AttendanceApiController::class, 'days'])->name('attendance.days');
        Route::get('attendance/time-logs', [AttendanceApiController::class, 'timeLogs'])->name('attendance.time-logs');
    });
    Route::post('attendance/time-logs', [AttendanceApiController::class, 'punch'])->middleware('abilities:attendance:write')->name('attendance.punch');

    Route::middleware('abilities:leaves:read')->group(function () {
        Route::get('leave-types', [LeavesApiController::class, 'types'])->name('leave-types');
        Route::get('leave-requests', [LeavesApiController::class, 'index'])->name('leave-requests.index');
    });
    Route::middleware('abilities:leaves:write')->group(function () {
        Route::post('leave-requests', [LeavesApiController::class, 'store'])->name('leave-requests.store');
        Route::post('leave-requests/{leaveRequest}/cancel', [LeavesApiController::class, 'cancel'])->whereNumber('leaveRequest')->name('leave-requests.cancel');
    });
});
