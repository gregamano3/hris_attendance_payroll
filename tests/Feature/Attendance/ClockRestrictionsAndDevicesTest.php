<?php

use App\Features\Attendance\Enums\TimeLogSource;
use App\Features\Attendance\Models\AttendanceDevice;
use App\Features\Attendance\Models\Shift;
use App\Features\Attendance\Models\TimeLog;
use App\Features\Employees\Models\Branch;
use App\Features\Employees\Models\Employee;
use App\Shared\Authorization\Role;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-10-05 07:55'));
    Shift::factory()->default()->create();
    // Makati office, 150 m geofence, office network 203.0.113.0/24.
    $this->branch = Branch::query()->create([
        'code' => 'MKT', 'name' => 'Makati', 'latitude' => '14.5547', 'longitude' => '121.0244',
        'geofence_radius_m' => 150, 'allowed_ip_ranges' => '203.0.113.0/24',
    ]);
    $this->user = userWithRole(Role::Employee);
    $this->employee = Employee::factory()->forUser($this->user)->create(['branch_id' => $this->branch->id, 'employee_no' => 'EMP-00001']);
});

it('allows clocking in from the office network', function () {
    $this->actingAs($this->user)->withServerVariables(['REMOTE_ADDR' => '203.0.113.25'])->post('/attendance/clock')->assertSessionHas('success');
});

it('allows clocking in within the geofence and stores only the distance', function () {
    $this->actingAs($this->user)->withServerVariables(['REMOTE_ADDR' => '198.51.100.7'])
        ->post('/attendance/clock', ['latitude' => '14.5550', 'longitude' => '121.0247'])
        ->assertSessionHas('success');

    $log = TimeLog::query()->sole();
    expect($log->distance_m)->toBeLessThan(150)
        ->and(array_keys($log->getAttributes()))->not->toContain('latitude');
});

it('refuses clocking in outside the geofence or without location', function () {
    $this->actingAs($this->user)->withServerVariables(['REMOTE_ADDR' => '198.51.100.7'])
        ->post('/attendance/clock', ['latitude' => '14.6091', 'longitude' => '121.0223']) // ~6 km away
        ->assertSessionHas('error', fn ($m) => str_contains($m, 'from Makati'));

    $this->actingAs($this->user)->withServerVariables(['REMOTE_ADDR' => '198.51.100.7'])
        ->post('/attendance/clock')->assertSessionHas('error', fn ($m) => str_contains($m, 'location'));

    expect(TimeLog::query()->count())->toBe(0);
});

it('does not restrict employees of branches without rules', function () {
    $this->employee->update(['branch_id' => Branch::query()->create(['code' => 'REM', 'name' => 'Remote'])->id]);

    $this->actingAs($this->user)->post('/attendance/clock')->assertSessionHas('success');
});

it('accepts punches from registered devices idempotently', function () {
    $device = AttendanceDevice::query()->create(['name' => 'Lobby', 'branch_id' => $this->branch->id, 'token_hash' => 'x']);
    $token = $device->issueToken();
    $payload = ['punches' => [
        ['employee_no' => 'EMP-00001', 'timestamp' => '2026-10-05T07:58:00+08:00', 'type' => 'in'],
        ['employee_no' => 'EMP-99999', 'timestamp' => '2026-10-05T07:59:00+08:00', 'type' => 'in'],
    ]];

    $this->withToken($token)->postJson('/api/attendance/punches', $payload)
        ->assertCreated()
        ->assertJson(['accepted' => 1, 'duplicates' => 0, 'rejected' => [['index' => 1, 'reason' => 'unknown employee_no']]]);

    $this->withToken($token)->postJson('/api/attendance/punches', $payload)->assertOk()->assertJson(['accepted' => 0, 'duplicates' => 1]);

    $log = TimeLog::query()->sole();
    expect($log->source)->toBe(TimeLogSource::Device)
        ->and($log->attendance_device_id)->toBe($device->id)
        ->and($log->logged_at->format('H:i'))->toBe('07:58')
        ->and($device->fresh()->last_seen_at)->not->toBeNull();
});

it('rejects invalid or disabled device tokens', function () {
    $device = AttendanceDevice::query()->create(['name' => 'Lobby', 'token_hash' => 'x']);
    $token = $device->issueToken();
    $payload = ['punches' => [['employee_no' => 'EMP-00001', 'timestamp' => '2026-10-05T07:58:00+08:00', 'type' => 'in']]];

    $this->withToken('wrong')->postJson('/api/attendance/punches', $payload)->assertUnauthorized();
    $device->update(['is_active' => false]);
    $this->withToken($token)->postJson('/api/attendance/punches', $payload)->assertUnauthorized();
    $this->postJson('/api/attendance/punches', $payload)->assertUnauthorized();
});

it('registers devices and shows the token once', function () {
    $hr = userWithRole(Role::Hr);

    $this->actingAs($hr)->post('/attendance/devices', ['name' => 'Gate', 'branch_id' => $this->branch->id])
        ->assertSessionHas('device_token', fn ($token) => str_starts_with($token, 'hrisdev_')
            && AttendanceDevice::findByToken($token)?->name === 'Gate');

    expect(AttendanceDevice::query()->sole()->token_hash)->toHaveLength(64);
    $this->actingAs($this->user)->get('/attendance/devices')->assertForbidden();
});
