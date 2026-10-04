<?php

use App\Features\Attendance\Clock\ClockController;
use App\Features\Attendance\ImportTimeLogs\ImportTimeLogsController;
use App\Features\Attendance\ManageDevices\DevicesController;
use App\Features\Attendance\ManageHolidays\HolidaysController;
use App\Features\Attendance\ManageLeaveTypes\LeaveTypesController;
use App\Features\Attendance\ManageShifts\ShiftsController;
use App\Features\Attendance\ManageTimeLogs\TimeLogsController;
use App\Features\Attendance\PrintDtr\PrintDtrController;
use App\Features\Attendance\RequestLeave\LeaveRequestsController;
use App\Features\Attendance\RequestOvertime\OvertimeRequestsController;
use App\Features\Attendance\ReviewLeaves\ReviewLeavesController;
use App\Features\Attendance\ReviewOvertime\ReviewOvertimeController;
use App\Features\Attendance\ShowDtr\ShowDtrController;
use App\Features\Attendance\ShowMyAttendance\ShowMyAttendanceController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::prefix('attendance')->name('attendance.')->group(function () {
        Route::middleware('can:attendance.clock')->group(function () {
            Route::get('clock', [ClockController::class, 'show'])->name('clock');
            Route::post('clock', [ClockController::class, 'store'])->middleware('throttle:10,1')->name('clock.store');
            Route::get('mine', ShowMyAttendanceController::class)->name('mine');
            Route::get('mine/pdf', [PrintDtrController::class, 'mine'])->name('mine.pdf');
        });

        Route::get('dtr', ShowDtrController::class)->middleware('can:attendance.view')->name('dtr');
        Route::get('dtr/{employee}/pdf', [PrintDtrController::class, 'forEmployee'])->middleware('can:attendance.view')->name('dtr.pdf');

        Route::middleware('can:attendance.manage')->group(function () {
            Route::get('logs', [TimeLogsController::class, 'index'])->name('logs.index');
            Route::post('logs', [TimeLogsController::class, 'store'])->name('logs.store');
            Route::delete('logs/{timeLog}', [TimeLogsController::class, 'destroy'])->name('logs.destroy');
            Route::get('import', [ImportTimeLogsController::class, 'create'])->name('import.create');
            Route::post('import', [ImportTimeLogsController::class, 'store'])->name('import.store');
        });
    });

    Route::middleware('can:attendance.manage')->group(function () {
        Route::post('shifts/assign', [ShiftsController::class, 'assign'])->name('shifts.assign');
        Route::resource('shifts', ShiftsController::class)->except('show');
        Route::resource('holidays', HolidaysController::class)->except(['show', 'create']);
        Route::resource('leave-types', LeaveTypesController::class)->only(['index', 'store', 'update']);
        Route::get('attendance/devices', [DevicesController::class, 'index'])->name('devices.index');
        Route::post('attendance/devices', [DevicesController::class, 'store'])->name('devices.store');
        Route::post('attendance/devices/{device}/token', [DevicesController::class, 'regenerate'])->name('devices.regenerate');
        Route::patch('attendance/devices/{device}/toggle', [DevicesController::class, 'toggle'])->name('devices.toggle');
    });

    Route::prefix('leaves')->name('leaves.')->group(function () {
        Route::middleware('can:leaves.request')->group(function () {
            Route::get('/', [LeaveRequestsController::class, 'index'])->name('index');
            Route::post('/', [LeaveRequestsController::class, 'store'])->name('store');
            Route::patch('{leaveRequest}/cancel', [LeaveRequestsController::class, 'cancel'])->name('cancel');
        });

        Route::middleware('can:review-leaves')->group(function () {
            Route::get('review', [ReviewLeavesController::class, 'index'])->name('review');
            Route::patch('{leaveRequest}/review', [ReviewLeavesController::class, 'update'])->name('review.update');
        });
    });

    Route::prefix('overtime')->name('overtime.')->group(function () {
        Route::middleware('can:overtime.request')->group(function () {
            Route::get('/', [OvertimeRequestsController::class, 'index'])->name('index');
            Route::post('/', [OvertimeRequestsController::class, 'store'])->name('store');
            Route::patch('{overtimeRequest}/cancel', [OvertimeRequestsController::class, 'cancel'])->name('cancel');
        });

        Route::middleware('can:review-overtime')->group(function () {
            Route::get('review', [ReviewOvertimeController::class, 'index'])->name('review');
            Route::patch('{overtimeRequest}/review', [ReviewOvertimeController::class, 'update'])->name('review.update');
        });
    });
});
