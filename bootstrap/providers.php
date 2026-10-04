<?php

use App\Features\Attendance\AttendanceServiceProvider;
use App\Features\Auth\AuthServiceProvider;
use App\Features\Dashboard\DashboardServiceProvider;
use App\Features\Employees\EmployeesServiceProvider;
use App\Features\Health\HealthServiceProvider;
use App\Features\Users\UsersServiceProvider;
use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,

    // Feature slices
    AttendanceServiceProvider::class,
    AuthServiceProvider::class,
    DashboardServiceProvider::class,
    EmployeesServiceProvider::class,
    HealthServiceProvider::class,
    UsersServiceProvider::class,
];
