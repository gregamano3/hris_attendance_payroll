<?php

use App\Features\Employees\Models\Employee;
use App\Features\Performance\Models\PerformanceReview;
use App\Features\Performance\Models\ReviewCycle;
use App\Features\Performance\Models\Training;
use App\Shared\Authorization\Role;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->hr = userWithRole(Role::Hr);
    $this->supervisorUser = userWithRole(Role::Employee);
    $this->supervisor = Employee::factory()->forUser($this->supervisorUser)->create(['hired_at' => '2020-01-01']);
    $this->user = userWithRole(Role::Employee);
    $this->employee = Employee::factory()->forUser($this->user)->create(['hired_at' => '2020-01-01', 'supervisor_id' => $this->supervisor->id]);
});

function launchCycle($test): ReviewCycle
{
    $test->actingAs($test->hr)->post('/performance/cycles', [
        'name' => '2026 annual', 'period_start' => '2026-01-01', 'period_end' => '2026-12-31', 'due_on' => '2027-01-31',
        'criteria' => "Quality | 3\nTeamwork | 1",
    ])->assertRedirect();

    return ReviewCycle::query()->sole();
}

it('launches a cycle with a review per employee assigned to the supervisor', function () {
    $cycle = launchCycle($this);

    expect($cycle->criteria)->toBe([['name' => 'Quality', 'weight' => 3], ['name' => 'Teamwork', 'weight' => 1]])
        ->and($cycle->reviews()->count())->toBe(2)
        ->and($cycle->reviews()->where('employee_id', $this->employee->id)->value('reviewer_id'))->toBe($this->supervisorUser->id);
});

it('runs the self-assessment, evaluation and acknowledgement flow', function () {
    launchCycle($this);
    $review = PerformanceReview::query()->where('employee_id', $this->employee->id)->sole();

    $this->actingAs($this->user)->put("/performance/reviews/{$review->id}/self", ['self_assessment' => 'Closed payroll on time all year.'])->assertSessionHas('success');
    $this->actingAs($this->user)->put("/performance/reviews/{$review->id}/complete", ['ratings' => [5, 5], 'comments' => 'x'])->assertForbidden(); // can't rate yourself

    $this->actingAs($this->supervisorUser)->get('/performance/reviews')->assertSee($this->employee->full_name);
    $this->actingAs($this->supervisorUser)->put("/performance/reviews/{$review->id}/complete", ['ratings' => [4, 2], 'comments' => 'Great quality, work on collaboration.'])
        ->assertRedirect('/performance/reviews');

    $review->refresh();
    expect($review->status)->toBe('completed')
        ->and($review->ratings)->toBe(['Quality' => 4, 'Teamwork' => 2])
        ->and((float) $review->overall_rating)->toBe(3.5); // (4×3 + 2×1) / 4

    $this->actingAs($this->user)->patch("/performance/reviews/{$review->id}/acknowledge")->assertSessionHas('success');
    expect($review->fresh()->status)->toBe('acknowledged');
});

it('keeps reviews private to the employee, reviewer and HR', function () {
    launchCycle($this);
    $review = PerformanceReview::query()->where('employee_id', $this->employee->id)->sole();
    $other = userWithRole(Role::Employee);
    Employee::factory()->forUser($other)->create();

    $this->actingAs($other)->get("/performance/reviews/{$review->id}")->assertForbidden();
    $this->actingAs($this->hr)->get("/performance/reviews/{$review->id}")->assertOk();
    $this->actingAs($this->user)->get('/performance/cycles')->assertForbidden();
});

it('records training with encrypted certificates and flags expiring ones', function () {
    Storage::fake('local');

    $this->actingAs($this->hr)->post('/performance/trainings', [
        'employee_id' => $this->employee->id, 'title' => 'Occupational First Aid', 'provider' => 'Red Cross',
        'completed_on' => today()->subYear()->toDateString(), 'hours' => '16', 'expires_on' => today()->addDays(20)->toDateString(),
        'certificate' => UploadedFile::fake()->createWithContent('cert.pdf', '%PDF CERT'),
    ])->assertSessionHas('success');

    $training = Training::query()->sole();
    expect(Storage::disk('local')->get($training->certificate_path))->not->toContain('CERT');
    expect($this->actingAs($this->user)->get("/performance/trainings/{$training->id}/certificate")->getContent())->toBe('%PDF CERT');

    $this->actingAs($this->hr)->get('/performance/trainings')->assertSee('Certifications expiring')->assertSee('Occupational First Aid');
});
