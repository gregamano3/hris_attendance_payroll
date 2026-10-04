<?php

namespace App\Features\Recruitment\ManageApplicants;

use App\Features\Employees\Enums\EmploymentStatus;
use App\Features\Employees\Enums\RateType;
use App\Features\Employees\Models\Employee;
use App\Features\Recruitment\Enums\ApplicantStage;
use App\Features\Recruitment\Models\Applicant;
use App\Features\Recruitment\Onboarding\StartOnboarding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Turns an applicant into an employee record and starts onboarding.
 */
class HireApplicantController
{
    public function __invoke(Request $request, Applicant $applicant, StartOnboarding $onboarding): RedirectResponse
    {
        if ($applicant->stage === ApplicantStage::Hired) {
            return back()->with('error', 'This applicant was already hired.');
        }

        $data = $request->validate([
            'employee_no' => ['required', 'string', 'max:30', Rule::unique('employees')],
            'hired_at' => ['required', 'date'],
            'rate_type' => ['required', Rule::enum(RateType::class)],
            'basic_rate' => ['required', 'numeric', 'gt:0'],
        ]);

        $employee = DB::transaction(function () use ($applicant, $data, $request, $onboarding) {
            $opening = $applicant->opening;
            $previousStage = $applicant->stage->value;

            $employee = Employee::query()->create([
                ...$data,
                'first_name' => $applicant->first_name,
                'last_name' => $applicant->last_name,
                'email' => $applicant->email,
                'mobile' => $applicant->mobile,
                'department_id' => $opening->department_id,
                'position_id' => $opening->position_id,
                'branch_id' => $opening->branch_id,
                'employment_type' => $opening->employment_type,
                'status' => EmploymentStatus::Active,
            ]);

            $applicant->update(['stage' => ApplicantStage::Hired, 'stage_changed_at' => now(), 'employee_id' => $employee->id]);
            $applicant->events()->create(['from_stage' => $previousStage, 'to_stage' => ApplicantStage::Hired->value,
                'note' => "Hired as {$employee->employee_no}", 'user_id' => $request->user()?->id]);

            $onboarding->handle($employee);

            if ($opening->applicants()->where('stage', ApplicantStage::Hired)->count() >= $opening->slots) {
                $opening->update(['status' => 'closed', 'closed_at' => now()]);
            }

            return $employee;
        });

        return redirect()->route('employees.show', $employee)->with('success', "{$employee->full_name} hired. Complete the employee record and the onboarding checklist.");
    }
}
