<?php

use App\Features\Account\AccountServiceProvider;
use App\Features\Attendance\AttendanceServiceProvider;
use App\Features\AuditLog\AuditLogServiceProvider;
use App\Features\Auth\AuthServiceProvider;
use App\Features\Dashboard\DashboardServiceProvider;
use App\Features\Employees\EmployeesServiceProvider;
use App\Features\Health\HealthServiceProvider;
use App\Features\Payroll\PayrollServiceProvider;
use App\Features\Security\SecurityServiceProvider;
use App\Features\Users\UsersServiceProvider;
use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,

    // Feature slices
    AccountServiceProvider::class,
    AttendanceServiceProvider::class,
    AuditLogServiceProvider::class,
    AuthServiceProvider::class,
    DashboardServiceProvider::class,
    EmployeesServiceProvider::class,
    HealthServiceProvider::class,
    PayrollServiceProvider::class,
    SecurityServiceProvider::class,
    UsersServiceProvider::class,
];
