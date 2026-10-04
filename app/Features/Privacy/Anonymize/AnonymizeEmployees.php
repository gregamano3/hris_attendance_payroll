<?php

namespace App\Features\Privacy\Anonymize;

use App\Features\Employees\Models\Employee;
use App\Features\Payroll\Models\Payslip;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Retention policy: once the legal retention period after separation has
 * passed, personal data is irreversibly removed. Amounts (payroll totals,
 * attendance minutes) are kept for statistics but no longer identify anyone.
 */
class AnonymizeEmployees
{
    /**
     * @return Collection<int, Employee> employees due for anonymization
     */
    public function due(int $years, ?Carbon $today = null): Collection
    {
        return Employee::withTrashed()
            ->whereNull('anonymized_at')
            ->whereNotNull('separated_at')
            ->whereDate('separated_at', '<=', ($today ?? today())->copy()->subYears($years))
            ->get();
    }

    public function anonymize(Employee $employee): void
    {
        DB::transaction(function () use ($employee) {
            $label = "Former employee #{$employee->id}";

            $employee->documents()->get()->each->delete(); // also deletes the encrypted files

            if ($employee->user !== null) {
                $employee->user->forceFill([
                    'name' => $label, 'email' => "anonymized-{$employee->user->id}@invalid.local", 'is_active' => false,
                    'two_factor_secret' => null, 'two_factor_recovery_codes' => null, 'two_factor_confirmed_at' => null,
                ])->save();
                $employee->user->syncRoles([]);
            }

            $employee->forceFill([
                'employee_no' => "ANON-{$employee->id}", 'first_name' => 'Former', 'middle_name' => null, 'last_name' => "Employee #{$employee->id}",
                'suffix' => null, 'birth_date' => null, 'gender' => null, 'civil_status' => null, 'email' => null, 'mobile' => null, 'address' => null,
                'sss_no' => null, 'philhealth_no' => null, 'pagibig_no' => null, 'tin' => null,
                'bank_name' => null, 'bank_account_name' => null, 'bank_account_no' => null,
                'user_id' => null, 'supervisor_id' => null, 'anonymized_at' => now(),
            ])->save();

            Payslip::query()->where('employee_id', $employee->id)->update(['employee_no' => "ANON-{$employee->id}", 'employee_name' => $label]);
            DB::table('leave_requests')->where('employee_id', $employee->id)->whereNotNull('attachment_path')->pluck('attachment_path')
                ->each(fn (string $path) => Storage::disk('local')->delete($path));
            DB::table('leave_requests')->where('employee_id', $employee->id)
                ->update(['reason' => null, 'attachment_path' => null, 'attachment_name' => null, 'attachment_mime' => null]);
            DB::table('overtime_requests')->where('employee_id', $employee->id)->update(['reason' => '[removed]']);
            DB::table('time_logs')->where('employee_id', $employee->id)->update(['ip_address' => null, 'remarks' => null]);
        });
    }
}
