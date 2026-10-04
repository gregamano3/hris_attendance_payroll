<?php

use App\Features\Employees\Models\Department;
use App\Features\Employees\Models\Employee;
use App\Features\Recruitment\Enums\ApplicantStage;
use App\Features\Recruitment\Models\Applicant;
use App\Features\Recruitment\Models\JobOpening;
use App\Features\Recruitment\Models\OnboardingTemplate;
use App\Shared\Authorization\Role;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->hr = userWithRole(Role::Hr);
    $this->department = Department::factory()->create();
});

function opening($test): JobOpening
{
    $test->actingAs($test->hr)->post('/recruitment/openings', [
        'title' => 'Payroll Associate', 'department_id' => $test->department->id, 'employment_type' => 'probationary', 'slots' => 1,
    ])->assertRedirect();

    return JobOpening::query()->sole();
}

it('creates openings and tracks applicants through the pipeline', function () {
    $opening = opening($this);

    $this->actingAs($this->hr)->post("/recruitment/openings/{$opening->id}/applicants", [
        'first_name' => 'Ana', 'last_name' => 'Reyes', 'email' => 'ana@example.com', 'mobile' => '09171112222',
        'resume' => UploadedFile::fake()->createWithContent('cv.pdf', '%PDF SECRET-CV'),
    ])->assertSessionHas('success');

    $applicant = Applicant::query()->sole();
    expect($applicant->stage)->toBe(ApplicantStage::Applied)
        ->and(DB::table('applicants')->value('mobile'))->not->toContain('09171112222')
        ->and(Storage::disk('local')->get($applicant->resume_path))->not->toContain('SECRET-CV');

    $this->actingAs($this->hr)->patch("/recruitment/applicants/{$applicant->id}/stage", ['stage' => 'interview', 'note' => 'Strong Excel skills'])->assertSessionHas('success');
    $this->actingAs($this->hr)->post("/recruitment/applicants/{$applicant->id}/notes", ['note' => 'References checked']);

    expect($applicant->fresh()->stage)->toBe(ApplicantStage::Interview)->and($applicant->events()->count())->toBe(3);
    expect($this->actingAs($this->hr)->get("/recruitment/applicants/{$applicant->id}/resume")->getContent())->toBe('%PDF SECRET-CV');
    $this->actingAs($this->hr)->get("/recruitment/openings/{$opening->id}")->assertOk()->assertSee('Ana Reyes');
});

it('hires an applicant into an employee record with onboarding tasks and closes the opening', function () {
    OnboardingTemplate::query()->create(['name' => 'Standard', 'is_default' => true, 'items' => [
        ['title' => 'Sign contract', 'due_after_days' => 0], ['title' => 'Submit NBI clearance', 'due_after_days' => 7],
    ]]);
    $opening = opening($this);
    $applicant = $opening->applicants()->create(['first_name' => 'Jose', 'last_name' => 'Cruz', 'email' => 'jose@example.com', 'stage' => ApplicantStage::Offer]);

    $this->actingAs($this->hr)->post("/recruitment/applicants/{$applicant->id}/hire", [
        'employee_no' => 'EMP-50001', 'hired_at' => '2026-11-03', 'rate_type' => 'monthly', 'basic_rate' => '28000',
    ])->assertRedirect();

    $employee = Employee::query()->where('employee_no', 'EMP-50001')->sole();
    expect($employee->first_name)->toBe('Jose')
        ->and($employee->department_id)->toBe($this->department->id)
        ->and($employee->onboardingTasks()->count())->toBe(2)
        ->and($employee->onboardingTasks()->where('title', 'Submit NBI clearance')->first()->due_on->toDateString())->toBe('2026-11-10')
        ->and($applicant->fresh()->stage)->toBe(ApplicantStage::Hired)
        ->and($opening->fresh()->status)->toBe('closed');

    $task = $employee->onboardingTasks()->first();
    $this->actingAs($this->hr)->patch("/recruitment/onboarding/tasks/{$task->id}");
    expect($task->fresh()->completed_at)->not->toBeNull();
    $this->actingAs($this->hr)->get('/recruitment/onboarding')->assertSee('1/2');
});

it('builds onboarding templates from lines', function () {
    $this->actingAs($this->hr)->post('/recruitment/onboarding/templates', ['name' => 'Ops', 'items' => "Safety training | 3\nIssue PPE", 'is_default' => '1']);

    expect(OnboardingTemplate::query()->sole()->items)->toBe([
        ['title' => 'Safety training', 'due_after_days' => 3], ['title' => 'Issue PPE', 'due_after_days' => 0],
    ]);
});

it('restricts recruitment to HR', function () {
    $this->actingAs(userWithRole(Role::Payroll))->get('/recruitment/openings')->assertForbidden();
});

it('deletes rejected applicants after the retention period', function () {
    $opening = opening($this);
    Storage::disk('local')->put('resumes/old.enc', 'x');
    $old = $opening->applicants()->create(['first_name' => 'Old', 'last_name' => 'Reject', 'email' => 'o@example.com', 'stage' => ApplicantStage::Rejected,
        'stage_changed_at' => now()->subMonths(13), 'resume_path' => 'resumes/old.enc']);
    $recent = $opening->applicants()->create(['first_name' => 'New', 'last_name' => 'Reject', 'email' => 'n@example.com', 'stage' => ApplicantStage::Rejected, 'stage_changed_at' => now()->subMonth()]);

    $this->artisan('privacy:anonymize')->expectsOutputToContain('Deleted 1 rejected applicant(s)')->assertSuccessful();

    $this->assertModelMissing($old);
    $this->assertModelExists($recent);
    Storage::disk('local')->assertMissing('resumes/old.enc');
});
