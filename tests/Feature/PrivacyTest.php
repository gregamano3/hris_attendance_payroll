<?php

use App\Features\Employees\Models\Employee;
use App\Features\Employees\Models\EmployeeDocument;
use App\Features\Payroll\Enums\PayrollRunStatus;
use App\Features\Payroll\Enums\PayrollRunType;
use App\Features\Payroll\Models\PayrollRun;
use App\Features\Payroll\Models\Payslip;
use App\Features\Privacy\Models\DataSubjectRequest;
use App\Features\Privacy\Models\PrivacyNotice;
use App\Models\User;
use App\Shared\Authorization\Role;
use App\Shared\Money\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->admin = userWithRole(Role::Admin);
    $this->user = userWithRole(Role::Employee);
    $this->employee = Employee::factory()->forUser($this->user)->create(['sss_no' => '3412345678', 'first_name' => 'Melchora']);
});

it('asks every user to acknowledge a published notice once per version', function () {
    $this->actingAs($this->user)->get('/dashboard')->assertOk(); // nothing published yet

    $this->actingAs($this->admin)->post('/privacy/notices', ['version' => '1.0', 'title' => 'Employee Privacy Notice', 'body' => 'We process...', 'publish' => '1']);

    $this->actingAs($this->user)->get('/dashboard')->assertRedirect('/privacy/notice');
    $this->actingAs($this->user)->get('/privacy/notice')->assertOk()->assertSee('We process...');
    $this->actingAs($this->user)->post('/privacy/notice')->assertRedirect('/dashboard');
    $this->actingAs($this->user)->get('/dashboard')->assertOk();

    // A new version requires a new acknowledgement.
    $this->actingAs($this->admin)->post('/privacy/notice'); // admin acknowledges v1
    $this->actingAs($this->admin)->post('/privacy/notices', ['version' => '2.0', 'title' => 'Updated', 'body' => 'New text', 'publish' => '1']);
    $this->actingAs($this->user)->get('/attendance/clock')->assertRedirect('/privacy/notice');
});

it('keeps drafts from blocking users', function () {
    $this->actingAs($this->admin)->post('/privacy/notices', ['version' => '1.0', 'title' => 'Draft', 'body' => 'x']);

    $this->actingAs($this->user)->get('/dashboard')->assertOk();
    expect(PrivacyNotice::query()->sole()->published_at)->toBeNull();
});

it('exports the user\'s personal data and logs the access request', function () {
    $run = PayrollRun::query()->create(['name' => 'P', 'type' => PayrollRunType::Regular, 'period_start' => '2026-09-01', 'period_end' => '2026-09-15', 'pay_date' => '2026-09-15', 'status' => PayrollRunStatus::Finalized]);
    Payslip::query()->create([
        'payroll_run_id' => $run->id, 'employee_id' => $this->employee->id, 'employee_no' => 'X', 'employee_name' => 'X', 'rate_type' => 'monthly',
        'basic_rate' => Money::ofPesos(1), 'daily_rate' => Money::zero(), 'hourly_rate' => Money::zero(), 'gross_pay' => Money::ofPesos(12345),
        'taxable_income' => Money::zero(), 'total_deductions' => Money::zero(), 'net_pay' => Money::ofPesos(12000), 'employer_contributions' => Money::zero(), 'attendance' => [],
    ]);

    $response = $this->actingAs($this->user)->get('/privacy/export')->assertOk()->assertHeader('content-disposition');
    $data = $response->json();

    expect($data['account']['email'])->toBe($this->user->email)
        ->and($data['employee']['name']['first'])->toBe('Melchora')
        ->and($data['employee']['government_ids']['sss_no'])->toBe('34-1234567-8')
        ->and($data['payslips'][0]['gross'])->toBe('12345.00')
        ->and(DataSubjectRequest::query()->sole()->status->value)->toBe('completed');
});

it('lets users file requests and the DPO answer them', function () {
    $this->actingAs($this->user)->post('/privacy/requests', ['type' => 'correction', 'details' => 'My birth date is wrong.'])->assertSessionHas('success');
    $request = DataSubjectRequest::query()->sole();

    $this->actingAs($this->user)->get('/privacy/requests')->assertForbidden();
    $this->actingAs($this->admin)->get('/privacy/requests')->assertOk()->assertSee('My birth date is wrong.');
    $this->actingAs($this->admin)->patch("/privacy/requests/{$request->id}", ['status' => 'completed', 'response' => 'Corrected.'])->assertSessionHas('success');

    $this->actingAs($this->user)->get('/privacy')->assertSee('Corrected.');
});

it('anonymizes employees after the retention period', function () {
    Storage::fake('local');
    $this->employee->update(['status' => 'resigned', 'separated_at' => '2015-06-30']);
    $document = EmployeeDocument::query()->create([
        'employee_id' => $this->employee->id, 'category' => 'contract', 'title' => 'Contract', 'path' => 'c.enc',
        'original_name' => 'c.pdf', 'mime_type' => 'application/pdf', 'size' => 1, 'is_encrypted' => true,
    ]);
    Storage::disk('local')->put('c.enc', 'x');
    $recent = Employee::factory()->create(['status' => 'resigned', 'separated_at' => now()->subYear()]);

    $this->artisan('privacy:anonymize', ['--years' => 10, '--dry-run' => true])->expectsOutputToContain('1 employee(s) would be anonymized')->assertSuccessful();
    $this->artisan('privacy:anonymize', ['--years' => 10])->assertSuccessful();

    $anon = $this->employee->fresh();
    expect($anon->anonymized_at)->not->toBeNull()
        ->and($anon->first_name)->toBe('Former')
        ->and($anon->sss_no)->toBeNull()
        ->and($anon->sss_no_bidx)->toBeNull()
        ->and($anon->user_id)->toBeNull()
        ->and($recent->fresh()->anonymized_at)->toBeNull()
        ->and(User::query()->find($this->user->id)->is_active)->toBeFalse()
        ->and(User::query()->find($this->user->id)->email)->toEndWith('@invalid.local');

    $this->assertModelMissing($document);
    Storage::disk('local')->assertMissing('c.enc');
    expect(DB::table('employees')->where('id', $this->employee->id)->value('last_name'))->toBe("Employee #{$this->employee->id}");
});
