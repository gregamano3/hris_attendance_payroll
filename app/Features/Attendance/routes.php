<?php

use App\Features\Attendance\Clock\ClockController;
use App\Features\Attendance\ImportTimeLogs\ImportTimeLogsController;
use App\Features\Attendance\ManageHolidays\HolidaysController;
use App\Features\Attendance\ManageShifts\ShiftsController;
use App\Features\Attendance\ManageTimeLogs\TimeLogsController;
use App\Features\Attendance\RequestLeave\LeaveRequestsController;
use App\Features\Attendance\ReviewLeaves\ReviewLeavesController;
use App\Features\Attendance\ShowDtr\ShowDtrController;
use App\Features\Attendance\ShowMyAttendance\ShowMyAttendanceController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::prefix('attendance')->name('attendance.')->group(function () {
        Route::middleware('can:attendance.clock')->group(function () {
            Route::get('clock', [ClockController::class, 'show'])->name('clock');
            Route::post('clock', [ClockController::class, 'store'])->middleware('throttle:10,1')->name('clock.store');
            Route::get('mine', ShowMyAttendanceController::class)->name('mine');
        });

        Route::get('dtr', ShowDtrController::class)->middleware('can:attendance.view')->name('dtr');

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
    });

    Route::prefix('leaves')->name('leaves.')->group(function () {
        Route::middleware('can:leaves.request')->group(function () {
            Route::get('/', [LeaveRequestsController::class, 'index'])->name('index');
            Route::post('/', [LeaveRequestsController::class, 'store'])->name('store');
            Route::patch('{leaveRequest}/cancel', [LeaveRequestsController::class, 'cancel'])->name('cancel');
        });

        Route::middleware('can:leaves.approve')->group(function () {
            Route::get('review', [ReviewLeavesController::class, 'index'])->name('review');
            Route::patch('{leaveRequest}/review', [ReviewLeavesController::class, 'update'])->name('review.update');
        });
    });
});
