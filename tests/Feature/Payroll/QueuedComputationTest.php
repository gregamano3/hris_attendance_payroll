<?php

use App\Features\Employees\Models\Employee;
use App\Features\Payroll\Compute\ComputePayrollRunJob;
use App\Features\Payroll\Enums\PayrollRunStatus;
use App\Features\Payroll\Enums\PayrollRunType;
use App\Features\Payroll\Models\PayrollRun;
use App\Features\Payroll\Models\StatutoryRate;
use App\Shared\Authorization\Role;
use Database\Seeders\PayrollSeeder;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->seed(PayrollSeeder::class);
    $this->officer = userWithRole(Role::Payroll);
    Employee::factory()->create(['hired_at' => '2020-01-01']);
    $this->run = PayrollRun::query()->create([
        'name' => 'Payroll', 'type' => PayrollRunType::Regular, 'period_start' => '2026-09-01',
        'period_end' => '2026-09-15', 'pay_date' => '2026-09-15', 'status' => PayrollRunStatus::Draft,
    ]);
});

it('queues the computation and marks the run as computing', function () {
    Queue::fake();

    $this->actingAs($this->officer)->post("/payroll/runs/{$this->run->id}/compute")
        ->assertSessionHas('status', 'Computing payslips in the background…');

    Queue::assertPushed(ComputePayrollRunJob::class, fn (ComputePayrollRunJob $job) => $job->run->is($this->run));
    expect($this->run->fresh()->status)->toBe(PayrollRunStatus::Computing);

    $this->actingAs($this->officer)->getJson("/payroll/runs/{$this->run->id}/status")
        ->assertOk()->assertJson(['status' => 'computing', 'progress' => 0]);
    $this->actingAs($this->officer)->get("/payroll/runs/{$this->run->id}")->assertSee('Computing payslips');
});

it('prevents double submits and edits while computing', function () {
    Queue::fake();
    $this->actingAs($this->officer)->post("/payroll/runs/{$this->run->id}/compute");

    $this->actingAs($this->officer)->post("/payroll/runs/{$this->run->id}/compute")
        ->assertSessionHas('warning', 'This payroll run is already being computed.');
    Queue::assertPushed(ComputePayrollRunJob::class, 1);

    $this->actingAs($this->officer)->delete("/payroll/runs/{$this->run->id}")->assertSessionHas('error');
    $this->actingAs($this->officer)->post("/payroll/runs/{$this->run->id}/finalize")->assertSessionHas('error');
});

it('completes the computation when the job runs', function () {
    $this->actingAs($this->officer)->post("/payroll/runs/{$this->run->id}/compute")
        ->assertSessionHas('success', 'Computed 1 payslip(s).');

    $run = $this->run->fresh();
    expect($run->status)->toBe(PayrollRunStatus::Computed)
        ->and($run->progress)->toBe(100)
        ->and($run->compute_error)->toBeNull();
});

it('returns the run to draft with the error when the computation fails', function () {
    StatutoryRate::query()->delete(); // no SSS rates → the calculator cannot be built

    $this->actingAs($this->officer)->post("/payroll/runs/{$this->run->id}/compute")->assertSessionHas('error');

    $run = $this->run->fresh();
    expect($run->status)->toBe(PayrollRunStatus::Draft)
        ->and($run->compute_error)->toContain('No SSS rates');

    $this->actingAs($this->officer)->get("/payroll/runs/{$this->run->id}")->assertSee('The last computation failed');
});
