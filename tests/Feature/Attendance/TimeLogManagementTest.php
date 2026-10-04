<?php

use App\Features\Attendance\Enums\AttendanceStatus;
use App\Features\Attendance\Enums\TimeLogSource;
use App\Features\Attendance\Models\AttendanceDay;
use App\Features\Attendance\Models\Shift;
use App\Features\Attendance\Models\TimeLog;
use App\Features\Employees\Models\Employee;
use App\Shared\Authorization\Role;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-10-10 12:00'));
    Shift::factory()->default()->create();
    $this->hr = userWithRole(Role::Hr);
    $this->employee = Employee::factory()->create(['employee_no' => 'EMP-00001']);
});

it('adds manual punches with an audit trail and recomputes', function () {
    $this->actingAs($this->hr)->post('/attendance/logs', [
        'employee_id' => $this->employee->id, 'logged_at' => '2026-10-05T08:00', 'type' => 'in', 'remarks' => 'Biometric down',
    ])->assertSessionHas('success');

    $this->actingAs($this->hr)->post('/attendance/logs', [
        'employee_id' => $this->employee->id, 'logged_at' => '2026-10-05T17:00', 'type' => 'out', 'remarks' => 'Biometric down',
    ]);

    $log = TimeLog::query()->first();
    expect($log->source)->toBe(TimeLogSource::Manual)
        ->and($log->created_by)->toBe($this->hr->id);

    expect(AttendanceDay::query()->whereDate('date', '2026-10-05')->first()->status)->toBe(AttendanceStatus::Present);
});

it('requires a reason for manual punches', function () {
    $this->actingAs($this->hr)->post('/attendance/logs', [
        'employee_id' => $this->employee->id, 'logged_at' => '2026-10-05T08:00', 'type' => 'in',
    ])->assertSessionHasErrors('remarks');
});

it('soft deletes punches recording who removed them', function () {
    $log = TimeLog::query()->create(['employee_id' => $this->employee->id, 'logged_at' => '2026-10-05 08:00', 'type' => 'in', 'source' => 'web']);

    $this->actingAs($this->hr)->delete("/attendance/logs/{$log->id}")->assertSessionHas('success');

    $log->refresh();
    expect($log->trashed())->toBeTrue()->and($log->deleted_by)->toBe($this->hr->id);
    expect(AttendanceDay::query()->whereDate('date', '2026-10-05')->first()->status)->toBe(AttendanceStatus::Absent);
});

it('imports punches from CSV, skipping duplicates and reporting bad rows', function () {
    $csv = implode("\n", [
        'employee_no,logged_at,type',
        'EMP-00001,2026-10-06 07:58,in',
        'EMP-00001,2026-10-06 17:02,out',
        'EMP-00001,2026-10-06 17:02,out',
        'EMP-99999,2026-10-06 08:00,in',
        'EMP-00001,not-a-date,in',
        'EMP-00001,2026-10-06 08:00,lunch',
    ]);

    $this->actingAs($this->hr)
        ->post('/attendance/import', ['file' => UploadedFile::fake()->createWithContent('punches.csv', $csv)])
        ->assertRedirect('/attendance/import')
        ->assertSessionHas('success', 'Imported 2 time log(s), skipped 1 duplicate(s).')
        ->assertSessionHas('import_errors', fn (array $errors) => count($errors) === 3);

    expect(TimeLog::query()->where('source', TimeLogSource::Import)->count())->toBe(2);
    expect(AttendanceDay::query()->whereDate('date', '2026-10-06')->first()->worked_minutes)->toBe(480);
});

it('rejects CSV files with a wrong header', function () {
    $this->actingAs($this->hr)
        ->post('/attendance/import', ['file' => UploadedFile::fake()->createWithContent('bad.csv', "id,time\n1,2")])
        ->assertSessionHas('import_errors', ['The header must contain employee_no, logged_at and type.']);
});

it('forbids payroll officers from managing time logs', function () {
    $this->actingAs(userWithRole(Role::Payroll))->get('/attendance/logs')->assertForbidden();
});
