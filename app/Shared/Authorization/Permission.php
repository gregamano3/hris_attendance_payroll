<?php

namespace App\Shared\Authorization;

/**
 * Every permission known to the application. Features check these through
 * the Gate (e.g. `can:employees.manage` middleware or `@can` in Blade).
 */
enum Permission: string
{
    case DashboardView = 'dashboard.view';
    case UsersManage = 'users.manage';

    case EmployeesView = 'employees.view';
    case EmployeesManage = 'employees.manage';

    case AttendanceClock = 'attendance.clock';
    case AttendanceView = 'attendance.view';
    case AttendanceManage = 'attendance.manage';

    case LeavesRequest = 'leaves.request';
    case LeavesApprove = 'leaves.approve';

    case PayrollView = 'payroll.view';
    case PayrollManage = 'payroll.manage';
    case PayrollFinalize = 'payroll.finalize';
    case PayslipsViewOwn = 'payslips.view-own';

    case SettingsManage = 'settings.manage';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
