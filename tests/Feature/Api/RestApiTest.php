<?php

use App\Features\Attendance\Enums\AttendanceStatus;
use App\Features\Attendance\Enums\LeaveStatus;
use App\Features\Attendance\Models\AttendanceDay;
use App\Features\Attendance\Models\LeaveRequest;
use App\Features\Attendance\Models\LeaveType;
use App\Features\Attendance\Models\Shift;
use App\Features\Attendance\Models\TimeLog;
use App\Features\Employees\Models\Employee;
use App\Features\Payroll\Enums\PayrollRunStatus;
use App\Features\Payroll\Enums\PayrollRunType;
use App\Features\Payroll\Models\PayrollRun;
use App\Models\User;
use App\Shared\Authorization\Role;
use Database\Seeders\PayrollSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Notification::fake();
    $this->travelTo(Carbon::parse('2026-10-20 12:00'));
    Shift::factory()->default()->create();
    $this->user = userWithRole(Role::Employee);
    $this->employee = Employee::factory()->forUser($this->user)->monthly(30000)->create(['hired_at' => '2020-01-01', 'sss_no' => '3412345678']);
});

/**
 * @param  list<string>  $abilities
 */
function apiToken(User $user, array $abilities = ['*']): string
{
    app('auth')->forgetGuards();

    return $user->createToken('test', $abilities)->plainTextToken;
}

it('manages personal access tokens from the account page', function () {
    $this->actingAs($this->user)->get('/account/api-tokens')->assertOk()->assertDontSee('employees:read');

    $this->actingAs($this->user)->post('/account/api-tokens', ['name' => 'Phone', 'abilities' => ['employees:read'], 'expires_in_days' => 30])
        ->assertSessionHasErrors('abilities.0');

    $response = $this->actingAs($this->user)->post('/account/api-tokens', ['name' => 'Phone', 'abilities' => ['profile:read', 'payslips:read'], 'expires_in_days' => 30]);
    $plain = $response->assertRedirect('/account/api-tokens')->assertSessionHas('plain_token')->getSession()->get('plain_token');

    $token = $this->user->tokens()->sole();
    expect($plain)->toContain('|hris_')
        ->and($token->abilities)->toBe(['profile:read', 'payslips:read'])
        ->and($token->expires_at->toDateString())->toBe('2026-11-19');

    $this->actingAs($this->user)->delete("/account/api-tokens/{$token->id}")->assertRedirect();
    expect($this->user->tokens()->count())->toBe(0);

    // Someone else's token cannot be revoked.
    $other = userWithRole(Role::Employee)->createToken('x')->accessToken;
    $this->actingAs($this->user)->delete("/account/api-tokens/{$other->id}")->assertNotFound();
});

it('authenticates bearer tokens and enforces scopes, expiry and active users', function () {
    $this->getJson('/api/v1/me')->assertUnauthorized();
    $this->withToken('hris_invalid')->getJson('/api/v1/me')->assertUnauthorized();

    $this->withToken(apiToken($this->user, ['profile:read']))->getJson('/api/v1/me')
        ->assertOk()
        ->assertJsonPath('data.email', $this->user->email)
        ->assertJsonPath('data.employee.employee_no', $this->employee->employee_no)
        ->assertJsonPath('data.token_abilities', ['profile:read'])
        ->assertJsonMissingPath('data.employee.sss_no');

    $this->withToken(apiToken($this->user, ['payslips:read']))->getJson('/api/v1/me')->assertForbidden();

    $expired = $this->user->createToken('old', ['*'], now()->subDay())->plainTextToken;
    app('auth')->forgetGuards();
    $this->withToken($expired)->getJson('/api/v1/me')->assertUnauthorized();

    $token = apiToken($this->user);
    $this->user->update(['is_active' => false]);
    $this->withToken($token)->getJson('/api/v1/me')->assertForbidden();

    // The web session never authenticates API calls.
    app('auth')->forgetGuards();
    $this->withoutToken()->actingAs($this->user, 'web')->getJson('/api/v1/me')->assertUnauthorized();
});

it('lists employees only for HR-capable tokens and never exposes government IDs', function () {
    $this->withToken(apiToken($this->user))->getJson('/api/v1/employees')->assertForbidden();

    $hr = userWithRole(Role::Hr);
    $this->withToken(apiToken($hr, ['profile:read']))->getJson('/api/v1/employees')->assertForbidden();

    $response = $this->withToken(apiToken($hr, ['employees:read']))->getJson('/api/v1/employees?per_page=10')
        ->assertOk()
        ->assertJsonPath('data.0.employee_no', $this->employee->employee_no)
        ->assertJsonPath('meta.per_page', 10);

    expect($response->getContent())->not->toContain('3412345678')->not->toContain('basic_rate');

    $this->withToken(apiToken($hr, ['employees:read']))->getJson("/api/v1/employees/{$this->employee->id}")->assertOk()->assertJsonPath('data.id', $this->employee->id);
    $this->withToken(apiToken($hr, ['employees:read']))->getJson('/api/v1/employees?status=bogus')->assertUnprocessable();
});

it('reads own attendance and restricts other employees', function () {
    AttendanceDay::query()->create(['employee_id' => $this->employee->id, 'date' => '2026-10-19', 'status' => AttendanceStatus::Present, 'worked_minutes' => 465, 'late_minutes' => 15]);
    $other = Employee::factory()->create();

    $this->withToken(apiToken($this->user, ['attendance:read']))->getJson('/api/v1/attendance/days?from=2026-10-01&to=2026-10-20')
        ->assertOk()
        ->assertJsonPath('data.0.date', '2026-10-19')
        ->assertJsonPath('data.0.late_minutes', 15)
        ->assertJsonPath('meta.employee_id', $this->employee->id);

    $this->withToken(apiToken($this->user, ['attendance:read']))->getJson("/api/v1/attendance/days?employee_id={$other->id}")->assertForbidden();
    $this->withToken(apiToken($this->user, ['attendance:read']))->getJson('/api/v1/attendance/days?from=2026-01-01&to=2026-10-20')->assertStatus(422);
    $this->withToken(apiToken(userWithRole(Role::Hr), ['attendance:read']))->getJson("/api/v1/attendance/days?employee_id={$other->id}")->assertOk();
});

it('clocks in and out through the API', function () {
    $this->withToken(apiToken($this->user, ['attendance:read']))->postJson('/api/v1/attendance/time-logs')->assertForbidden();

    $token = apiToken($this->user, ['attendance:write', 'attendance:read']);
    $this->withToken($token)->postJson('/api/v1/attendance/time-logs')->assertCreated()->assertJsonPath('data.type', 'in')->assertJsonPath('data.source', 'api');
    $this->withToken($token)->postJson('/api/v1/attendance/time-logs', ['type' => 'in'])->assertUnprocessable()->assertJsonValidationErrors('punch');

    $this->travel(9)->hours();
    $this->withToken($token)->postJson('/api/v1/attendance/time-logs')->assertCreated()->assertJsonPath('data.type', 'out');

    expect(TimeLog::query()->where('employee_id', $this->employee->id)->pluck('type')->map->value->all())->toBe(['in', 'out']);
    $this->withToken($token)->getJson('/api/v1/attendance/time-logs?from=2026-10-20&to=2026-10-20')->assertOk()->assertJsonCount(2, 'data');
});

it('files, lists and cancels leave requests', function () {
    $vl = LeaveType::query()->create(['code' => 'VL', 'name' => 'Vacation Leave', 'is_paid' => true, 'days_per_year' => 15]);

    $this->withToken(apiToken($this->user, ['leaves:read']))->postJson('/api/v1/leave-requests', [])->assertForbidden();

    $token = apiToken($this->user, ['leaves:read', 'leaves:write']);
    $this->withToken($token)->getJson('/api/v1/leave-types')->assertOk()->assertJsonPath('data.0.code', 'VL');
    $this->withToken($token)->postJson('/api/v1/leave-requests', ['leave_type_id' => $vl->id, 'start_date' => '2026-10-27', 'end_date' => '2026-10-26'])
        ->assertUnprocessable()->assertJsonValidationErrors('end_date');

    $id = $this->withToken($token)->postJson('/api/v1/leave-requests', ['leave_type_id' => $vl->id, 'start_date' => '2026-10-26', 'end_date' => '2026-10-27', 'reason' => 'Trip'])
        ->assertCreated()
        ->assertJsonPath('data.days', 2)
        ->assertJsonPath('data.status', 'pending')
        ->json('data.id');

    $this->withToken($token)->getJson('/api/v1/leave-requests')->assertOk()->assertJsonPath('meta.total', 1);
    $this->withToken($token)->postJson("/api/v1/leave-requests/{$id}/cancel")->assertOk()->assertJsonPath('data.status', 'cancelled');
    $this->withToken($token)->postJson("/api/v1/leave-requests/{$id}/cancel")->assertStatus(409);

    $stranger = userWithRole(Role::Employee);
    Employee::factory()->forUser($stranger)->create();
    $this->withToken(apiToken($stranger, ['leaves:write']))->postJson("/api/v1/leave-requests/{$id}/cancel")->assertNotFound();
    expect($this->employee->fresh()?->id)->not->toBeNull()
        ->and(LeaveRequest::query()->find($id)?->status)->toBe(LeaveStatus::Cancelled);
});

it('returns only own payslips from finalized runs', function () {
    $this->seed(PayrollSeeder::class);
    $officer = userWithRole(Role::Payroll);
    $run = PayrollRun::query()->create(['name' => 'Oct A', 'type' => PayrollRunType::Regular, 'period_start' => '2026-10-01', 'period_end' => '2026-10-15', 'pay_date' => '2026-10-15', 'status' => PayrollRunStatus::Draft]);
    $this->actingAs($officer)->post("/payroll/runs/{$run->id}/compute");
    $payslip = $run->payslips()->sole();

    $token = apiToken($this->user, ['payslips:read']);
    $this->withToken($token)->getJson('/api/v1/payslips')->assertOk()->assertJsonCount(0, 'data');
    $this->withToken($token)->getJson("/api/v1/payslips/{$payslip->id}")->assertNotFound();

    $run->update(['status' => PayrollRunStatus::Finalized]);

    $this->withToken($token)->getJson('/api/v1/payslips')->assertOk()->assertJsonPath('data.0.id', $payslip->id)->assertJsonPath('data.0.currency', 'PHP');
    $this->withToken($token)->getJson("/api/v1/payslips/{$payslip->id}")
        ->assertOk()
        ->assertJsonPath('data.net_pay', $payslip->net_pay->toDecimal())
        ->assertJsonPath('data.lines.0.code', 'BASIC');

    $stranger = userWithRole(Role::Employee);
    Employee::factory()->forUser($stranger)->create();
    $this->withToken(apiToken($stranger, ['payslips:read']))->getJson("/api/v1/payslips/{$payslip->id}")->assertNotFound();
});

it('rate limits per user', function () {
    config(['hris.api.rate_limit_per_minute' => 2]);
    $token = apiToken($this->user, ['profile:read']);

    $this->withToken($token)->getJson('/api/v1/me')->assertOk();
    $this->withToken($token)->getJson('/api/v1/me')->assertOk();
    $this->withToken($token)->getJson('/api/v1/me')->assertTooManyRequests()->assertHeader('Retry-After');
});

it('documents every v1 endpoint in the OpenAPI file', function () {
    $yaml = $this->get('/api/v1/openapi.yaml')->assertOk()->assertHeader('Content-Type', 'application/yaml')->getContent();

    expect($yaml)->toContain('openapi: 3.1.0')->toContain(config('app.url').'/api/v1');

    collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route) => str_starts_with($route->uri(), 'api/v1/') && $route->uri() !== 'api/v1/openapi.yaml')
        ->each(function ($route) use ($yaml) {
            $path = preg_replace('/\{[^}]+\}/', '{id}', substr($route->uri(), strlen('api/v1')));
            expect($yaml)->toContain("  {$path}:");
        });
});

it('can be switched off', function () {
    config(['hris.api.enabled' => false]);

    $this->withToken(apiToken($this->user))->getJson('/api/v1/me')->assertNotFound();
    $this->get('/api/v1/openapi.yaml')->assertNotFound();
});
