<?php

namespace App\Features\Privacy\ExportMyData;

use App\Features\Attendance\Models\AttendanceDay;
use App\Features\Attendance\Models\LeaveRequest;
use App\Features\Attendance\Models\OvertimeRequest;
use App\Features\Attendance\Models\TimeLog;
use App\Features\Employees\Enums\GovernmentId;
use App\Features\Employees\Models\Employee;
use App\Features\Payroll\Enums\PayrollRunStatus;
use App\Features\Payroll\Models\Payslip;
use App\Features\Payroll\Models\PayslipLine;
use App\Models\User;

/**
 * Everything the system holds about a person (RA 10173 right of access).
 */
class PersonalDataExport
{
    /**
     * @return array<string, mixed>
     */
    public function for(User $user, ?Employee $employee): array
    {
        $data = [
            'generated_at' => now()->toIso8601String(),
            'controller' => config('hris.employer.name'),
            'account' => [
                'name' => $user->name, 'email' => $user->email, 'role' => $user->primaryRole()?->label(),
                'created_at' => $user->created_at?->toIso8601String(), 'last_login_at' => $user->last_login_at?->toIso8601String(),
                'two_factor_enabled' => $user->hasTwoFactorEnabled(),
            ],
        ];

        if ($employee === null) {
            return $data;
        }

        $employee->loadMissing(['department', 'position', 'supervisor', 'documents', 'compensationChanges']);

        $data['employee'] = [
            'employee_no' => $employee->employee_no,
            'name' => ['first' => $employee->first_name, 'middle' => $employee->middle_name, 'last' => $employee->last_name, 'suffix' => $employee->suffix],
            'birth_date' => $employee->birth_date?->toDateString(),
            'gender' => $employee->gender?->value,
            'civil_status' => $employee->civil_status?->value,
            'contact' => ['email' => $employee->email, 'mobile' => $employee->mobile, 'address' => $employee->address],
            'employment' => [
                'department' => $employee->department?->name, 'position' => $employee->position?->title,
                'supervisor' => $employee->supervisor?->full_name, 'type' => $employee->employment_type->value,
                'status' => $employee->status->value, 'hired_at' => $employee->hired_at->toDateString(),
                'regularized_at' => $employee->regularized_at?->toDateString(), 'separated_at' => $employee->separated_at?->toDateString(),
            ],
            'government_ids' => collect(GovernmentId::cases())->mapWithKeys(fn (GovernmentId $id) => [$id->value => $employee->governmentId($id)])->all(),
            'bank' => ['bank' => $employee->bank_name, 'account_name' => $employee->bank_account_name, 'account_no' => $employee->bank_account_no],
            'salary_history' => $employee->compensationChanges->map(fn ($c) => [
                'effective_from' => $c->effective_from->toDateString(), 'rate_type' => $c->rate_type->value, 'basic_rate' => $c->basic_rate->toDecimal(),
            ])->values()->all(),
            'documents' => $employee->documents->map(fn ($d) => ['title' => $d->title, 'category' => $d->category->value, 'uploaded_at' => $d->created_at?->toIso8601String()])->values()->all(),
        ];

        $data['time_logs'] = TimeLog::query()->where('employee_id', $employee->id)->orderBy('logged_at')->get()
            ->map(fn (TimeLog $l) => ['at' => $l->logged_at->toIso8601String(), 'type' => $l->type->value, 'source' => $l->source->value])->all();
        $data['attendance'] = AttendanceDay::query()->where('employee_id', $employee->id)->orderBy('date')->get()
            ->map(fn (AttendanceDay $d) => ['date' => $d->date->toDateString(), 'status' => $d->status->value, 'worked_minutes' => $d->worked_minutes,
                'late_minutes' => $d->late_minutes, 'overtime_minutes' => $d->overtime_minutes])->all();
        $data['leave_requests'] = LeaveRequest::query()->with('leaveType')->where('employee_id', $employee->id)->get()
            ->map(fn (LeaveRequest $r) => ['type' => $r->leaveType->name, 'from' => $r->start_date->toDateString(), 'to' => $r->end_date->toDateString(),
                'days' => (float) $r->days, 'status' => $r->status->value, 'reason' => $r->reason])->all();
        $data['overtime_requests'] = OvertimeRequest::query()->where('employee_id', $employee->id)->get()
            ->map(fn (OvertimeRequest $r) => ['date' => $r->date->toDateString(), 'minutes' => $r->minutes, 'status' => $r->status->value, 'reason' => $r->reason])->all();
        $data['payslips'] = Payslip::query()->with(['run', 'lines'])->where('employee_id', $employee->id)
            ->whereHas('run', fn ($q) => $q->where('status', PayrollRunStatus::Finalized))->get()
            ->map(fn (Payslip $p): array => [
                'period' => $p->run->period()->label(), 'gross' => $p->gross_pay->toDecimal(), 'deductions' => $p->total_deductions->toDecimal(),
                'net' => $p->net_pay->toDecimal(),
                'lines' => $p->lines->map(fn (PayslipLine $l): array => ['label' => $l->label, 'amount' => $l->amount->toDecimal()])->all(),
            ])->all();

        return $data;
    }
}
