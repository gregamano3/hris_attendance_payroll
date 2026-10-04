<?php

use App\Features\Attendance\DeviceApi\AuthenticateDevice;
use App\Features\Attendance\DeviceApi\DevicePunchesController;
use Illuminate\Support\Facades\Route;

Route::post('attendance/punches', DevicePunchesController::class)
    ->middleware([AuthenticateDevice::class, 'throttle:120,1'])
    ->name('attendance.punches');
