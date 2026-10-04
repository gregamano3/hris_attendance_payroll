<?php

use App\Features\Employees\Models\Employee;
use App\Models\User;
use App\Shared\Audit\AuditLog;
use App\Shared\Authorization\Role;

beforeEach(fn () => $this->admin = userWithRole(Role::Admin));

it('records who created, updated and deleted a record with the changed values', function () {
    $hr = userWithRole(Role::Hr);
    $employee = Employee::factory()->monthly(30000)->create(['first_name' => 'Ana']);

    $this->actingAs($hr)->put("/employees/{$employee->id}", [
        'employee_no' => $employee->employee_no, 'first_name' => 'Anna', 'last_name' => $employee->last_name,
        'employment_type' => 'regular', 'status' => 'active', 'hired_at' => $employee->hired_at->toDateString(),
        'rate_type' => 'monthly', 'basic_rate' => '25000',
    ])->assertSessionHasNoErrors();
    $this->actingAs($hr)->delete("/employees/{$employee->id}");

    $logs = AuditLog::query()->where('auditable_type', Employee::class)->where('auditable_id', $employee->id)->orderBy('id')->get();

    expect($logs->pluck('event')->all())->toBe(['created', 'updated', 'deleted'])
        ->and($logs[1]->user_id)->toBe($hr->id)
        ->and($logs[1]->old_values['first_name'])->toBe('Ana')
        ->and($logs[1]->new_values['first_name'])->toBe('Anna')
        ->and($logs[1]->new_values['basic_rate'])->toBe(2500000)
        ->and($logs[2]->user_id)->toBe($hr->id)
        ->and($logs[2]->old_values['first_name'])->toBe('Anna')
        ->and($logs[1]->url)->toContain("/employees/{$employee->id}");
});

it('never stores passwords or remember tokens', function () {
    $this->actingAs($this->admin)->post('/users', [
        'name' => 'Jose', 'email' => 'jose@example.com', 'password' => 'secret-pass', 'password_confirmation' => 'secret-pass',
        'role' => 'employee', 'is_active' => '1',
    ]);

    $log = AuditLog::query()->where('auditable_type', User::class)->where('event', 'created')->latest('id')->firstOrFail();

    expect($log->new_values['password'])->toBe('[redacted]')
        ->and(json_encode($log->new_values))->not->toContain('secret-pass');
});

it('skips updates without meaningful changes', function () {
    $user = User::factory()->create();
    $before = AuditLog::query()->count();

    $user->forceFill(['last_login_at' => now()])->save();

    expect(AuditLog::query()->count())->toBe($before);
});

it('shows and filters the audit log for administrators only', function () {
    $employee = Employee::factory()->create(['first_name' => 'Gabriela']);

    $this->actingAs($this->admin)->get('/audit-log')->assertOk()->assertSee('Employee #'.$employee->id);
    $this->actingAs($this->admin)->get('/audit-log?type='.urlencode(Employee::class)."&id={$employee->id}&event=created")
        ->assertOk()->assertSee('Gabriela');

    $this->actingAs(userWithRole(Role::Hr))->get('/audit-log')->assertForbidden();
});
