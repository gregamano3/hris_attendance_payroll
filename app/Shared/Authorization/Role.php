<?php

namespace App\Shared\Authorization;

enum Role: string
{
    case Admin = 'admin';
    case Hr = 'hr';
    case Payroll = 'payroll';
    case Employee = 'employee';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Hr => 'Human Resources',
            self::Payroll => 'Payroll Officer',
            self::Employee => 'Employee',
        };
    }

    /**
     * Permissions granted to the role. Admins bypass every check through
     * Gate::before, so they don't need explicit permissions.
     *
     * @return list<Permission>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::Admin => Permission::cases(),
            self::Hr => [
                Permission::DashboardView,
                Permission::EmployeesView,
                Permission::EmployeesManage,
                Permission::AttendanceClock,
                Permission::AttendanceView,
                Permission::AttendanceManage,
                Permission::LeavesRequest,
                Permission::LeavesApprove,
                Permission::PayslipsViewOwn,
            ],
            self::Payroll => [
                Permission::DashboardView,
                Permission::EmployeesView,
                Permission::AttendanceClock,
                Permission::AttendanceView,
                Permission::LeavesRequest,
                Permission::PayrollView,
                Permission::PayrollManage,
                Permission::PayrollFinalize,
                Permission::PayslipsViewOwn,
                Permission::SettingsManage,
            ],
            self::Employee => [
                Permission::DashboardView,
                Permission::AttendanceClock,
                Permission::LeavesRequest,
                Permission::PayslipsViewOwn,
            ],
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $role) {
            $options[$role->value] = $role->label();
        }

        return $options;
    }
}
