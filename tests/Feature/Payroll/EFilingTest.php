<?php

use App\Features\Attendance\Models\Shift;
use App\Features\Attendance\Models\TimeLog;
use App\Features\Employees\Models\Employee;
use App\Features\Payroll\Enums\PayrollRunStatus;
use App\Features\Payroll\Enums\PayrollRunType;
use App\Features\Payroll\Models\PayrollRun;
use App\Shared\Authorization\Role;
use Database\Seeders\PayrollSeeder;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-11-10 12:00'));
    $this->seed(PayrollSeeder::class);
    Shift::factory()->default()->create();
    config(['hris.employer' => [
        'name' => 'Acme Manufacturing Inc.', 'address' => 'Makati', 'tin' => '111-222-333', 'tin_branch' => '0000', 'rdo' => '050',
        'sss_no' => '03-9876543-2', 'philhealth_no' => '', 'pagibig_no' => '', 'bank_company_code' => '', 'bank_account_no' => '',
    ]]);
    $this->officer = userWithRole(Role::Payroll);
    $this->employee = Employee::factory()->monthly(30000)->create([
        'hired_at' => '2020-01-01', 'first_name' => 'José', 'last_name' => 'Rizal', 'middle_name' => 'Mercado', 'gender' => 'male',
        'birth_date' => '1990-06-19', 'sss_no' => '3412345678', 'philhealth_no' => '123456789012', 'pagibig_no' => '121212121212', 'tin' => '123456789',
    ]);

    for ($date = Carbon::parse('2026-10-01'); $date->lte('2026-10-15'); $date->addDay()) {
        if (! $date->isWeekend()) {
            TimeLog::query()->create(['employee_id' => $this->employee->id, 'logged_at' => $date->copy()->setTime(8, 0), 'type' => 'in', 'source' => 'import']);
            TimeLog::query()->create(['employee_id' => $this->employee->id, 'logged_at' => $date->copy()->setTime(17, 0), 'type' => 'out', 'source' => 'import']);
        }
    }

    $run = PayrollRun::query()->create([
        'name' => 'Oct 1–15', 'type' => PayrollRunType::Regular, 'period_start' => '2026-10-01', 'period_end' => '2026-10-15',
        'pay_date' => '2026-10-15', 'status' => PayrollRunStatus::Draft,
    ]);
    $this->actingAs($this->officer)->post("/payroll/runs/{$run->id}/compute");
    $this->actingAs($this->officer)->post("/payroll/runs/{$run->id}/finalize");
});

function efile($test, string $query): string
{
    return $test->actingAs($test->officer)->get("/payroll/reports/efile/{$query}")->assertOk()->getContent();
}

it('builds the SSS R3 fixed-width file', function () {
    $lines = explode("\r\n", trim(efile($this, 'sss-r3?month=2026-10')));

    expect($lines)->toHaveCount(3)
        ->and($lines[0])->toBe('00'.'0398765432'.str_pad('ACME MANUFACTURING INC.', 40).'202610')
        ->and($lines[1])->toBe('20'.'3412345678'.str_pad('RIZAL', 20).str_pad('JOSE', 20).'M'.'000225000'.'0001500') // SS 750+1,500, EC 15
        ->and($lines[2])->toBe('99'.'000001'.'000000225000'.'0000001500');
});

it('builds the PhilHealth RF-1 and Pag-IBIG MCRF uploads', function () {
    $rf1 = array_map('str_getcsv', explode("\n", trim(efile($this, 'philhealth-rf1?month=2026-10'))));
    expect($rf1[1])->toBe(['123456789012', 'RIZAL', 'JOSE', 'MERCADO', '06/19/1990', 'M', '30000.00', '375.00', '375.00', 'A', '102026']);

    $mcrf = array_map('str_getcsv', explode("\n", trim(efile($this, 'pagibig-mcrf?month=2026-10'))));
    expect($mcrf[1])->toBe(['121212121212', 'RIZAL', 'JOSE', 'MERCADO', '06/19/1990', '202610', '30000.00', '100.00', '100.00', '123456789', 'F1']);
});

it('builds the BIR alphalist DAT with header, schedule and control records', function () {
    $response = $this->actingAs($this->officer)->get('/payroll/reports/efile/bir-alphalist?year=2026')->assertOk();
    $lines = explode("\r\n", trim($response->getContent()));

    expect($response->headers->get('content-disposition'))->toContain('111222333000012312026')
        ->and($lines[0])->toBe('H1604C,111222333,0000,12/31/2026,050')
        ->and($lines[1])->toStartWith('D1,1604C,111222333,0000,12/31/2026,1,123456789,"RIZAL","JOSE","MERCADO",15000.00,')
        ->and($lines[2])->toStartWith('C1,1604C,111222333,0000,12/31/2026,1,15000.00,');
});

it('rejects unknown e-filing formats', function () {
    $this->actingAs($this->officer)->get('/payroll/reports/efile/nope')->assertNotFound();
});
