<?php

use App\Features\Employees\Enums\GovernmentId;
use App\Features\Employees\Models\Employee;
use App\Features\Employees\Models\EmployeeDocument;
use App\Shared\Audit\AuditLog;
use App\Shared\Authorization\Role;
use App\Shared\Security\EncryptedFiles;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Encryption\Encrypter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

function employeeWithPii(): Employee
{
    return Employee::factory()->create([
        'birth_date' => '1990-05-14', 'mobile' => '09171234567', 'address' => '123 Rizal St., Manila',
        'sss_no' => '3412345678', 'philhealth_no' => '123456789012', 'pagibig_no' => '121212121212',
        'tin' => '123456789', 'bank_account_name' => 'Juan Dela Cruz', 'bank_account_no' => '001234567890',
    ]);
}

it('stores PII encrypted and reads it back transparently', function () {
    $employee = employeeWithPii();
    $raw = (array) DB::table('employees')->where('id', $employee->id)->first();

    foreach (['3412345678', '123456789012', '121212121212', '123456789', '09171234567', 'Rizal', '001234567890', 'Juan Dela Cruz', '1990-05-14'] as $plain) {
        expect(json_encode($raw))->not->toContain($plain);
    }

    $fresh = $employee->fresh();
    expect($fresh->sss_no)->toBe('3412345678')
        ->and($fresh->birth_date->toDateString())->toBe('1990-05-14')
        ->and($fresh->address)->toBe('123 Rizal St., Manila')
        ->and($fresh->governmentId(GovernmentId::Sss))->toBe('34-1234567-8');
});

it('keeps government IDs unique and searchable through blind indexes', function () {
    $employee = employeeWithPii();

    expect(Employee::findByGovernmentId(GovernmentId::Tin, '123-456-789')?->is($employee))->toBeTrue()
        ->and(DB::table('employees')->where('id', $employee->id)->value('tin_bidx'))->toHaveLength(64);

    $this->actingAs(userWithRole(Role::Hr))->post('/employees', [
        'employee_no' => 'EMP-99999', 'first_name' => 'Dup', 'last_name' => 'Licate', 'employment_type' => 'regular',
        'status' => 'active', 'hired_at' => '2024-01-01', 'rate_type' => 'monthly', 'basic_rate' => '20000',
        'sss_no' => '34-1234567-8',
    ])->assertSessionHasErrors(['sss_no' => 'The SSS No. has already been taken.']);

    expect(fn () => Employee::factory()->create(['sss_no' => '3412345678']))->toThrow(UniqueConstraintViolationException::class);
});

it('never writes PII values to the audit log', function () {
    $employee = employeeWithPii();
    $employee->update(['mobile' => '09998887777']);

    $logs = AuditLog::query()->where('auditable_id', $employee->id)->where('auditable_type', Employee::class)->get();
    $json = $logs->toJson();

    expect($json)->not->toContain('09998887777')->not->toContain('3412345678')->not->toContain('_bidx')
        ->and($logs->last()->new_values['mobile'])->toBe('[encrypted]');
});

it('encrypts uploaded documents on disk', function () {
    Storage::fake('local');
    $employee = employeeWithPii();
    $hr = userWithRole(Role::Hr);

    $this->actingAs($hr)->post("/employees/{$employee->id}/documents", [
        'category' => 'government_id', 'title' => 'UMID',
        'file' => UploadedFile::fake()->createWithContent('umid.pdf', '%PDF-1.4 SECRET-UMID-CONTENT'),
    ]);

    $document = EmployeeDocument::query()->sole();
    expect($document->is_encrypted)->toBeTrue()
        ->and(Storage::disk('local')->get($document->path))->not->toContain('SECRET-UMID-CONTENT');

    $response = $this->actingAs($hr)->get("/documents/{$document->id}")->assertOk();
    expect($response->streamedContent())->toBe('%PDF-1.4 SECRET-UMID-CONTENT');
});

it('rotates keys with security:reencrypt', function () {
    Storage::fake('local');
    $employee = employeeWithPii();
    Storage::disk('local')->put('legacy.pdf', 'PLAINTEXT-LEGACY');
    $legacy = EmployeeDocument::query()->create([
        'employee_id' => $employee->id, 'category' => 'other', 'title' => 'Legacy', 'path' => 'legacy.pdf',
        'original_name' => 'legacy.pdf', 'mime_type' => 'application/pdf', 'size' => 16, 'is_encrypted' => false,
    ]);
    $before = DB::table('employees')->where('id', $employee->id)->value('sss_no');

    // Rotate: the old key becomes a previous key.
    $oldKey = config('app.key');
    $newKey = 'base64:'.base64_encode(Encrypter::generateKey(config('app.cipher')));
    config(['app.key' => $newKey, 'app.previous_keys' => [$oldKey]]);
    app()->forgetInstance('encrypter');
    Crypt::clearResolvedInstance('encrypter');

    $this->artisan('security:reencrypt')->assertSuccessful();

    $after = DB::table('employees')->where('id', $employee->id)->value('sss_no');
    $onlyNewKey = new Encrypter(base64_decode(substr($newKey, 7)), config('app.cipher'));

    expect($after)->not->toBe($before)
        ->and($onlyNewKey->decryptString($after))->toBe('3412345678')
        ->and($legacy->fresh()->is_encrypted)->toBeTrue()
        ->and(Storage::disk('local')->get('legacy.pdf'))->not->toContain('PLAINTEXT-LEGACY')
        ->and(EncryptedFiles::get('local', 'legacy.pdf'))->toBe('PLAINTEXT-LEGACY');
});
