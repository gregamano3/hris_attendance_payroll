<?php

namespace App\Features\Api;

use App\Models\User;
use App\Shared\Authorization\Permission;

/**
 * Token scopes. A token can only reach what its scopes allow AND what the
 * owner's role permits; endpoints check both.
 */
enum ApiAbility: string
{
    case ProfileRead = 'profile:read';
    case EmployeesRead = 'employees:read';
    case AttendanceRead = 'attendance:read';
    case AttendanceWrite = 'attendance:write';
    case LeavesRead = 'leaves:read';
    case LeavesWrite = 'leaves:write';
    case PayslipsRead = 'payslips:read';

    public function description(): string
    {
        return match ($this) {
            self::ProfileRead => 'Read your account and employee profile',
            self::EmployeesRead => 'Read the employee directory (no government IDs or pay)',
            self::AttendanceRead => 'Read attendance days and time logs',
            self::AttendanceWrite => 'Clock in and out',
            self::LeavesRead => 'Read your leave requests and leave types',
            self::LeavesWrite => 'File and cancel your leave requests',
            self::PayslipsRead => 'Read your finalized payslips',
        };
    }

    /**
     * Scopes the user may grant to a new token.
     *
     * @return list<self>
     */
    public static function grantableBy(User $user): array
    {
        return array_values(array_filter(self::cases(), fn (self $a) => $a !== self::EmployeesRead || $user->can(Permission::EmployeesView->value)));
    }
}
