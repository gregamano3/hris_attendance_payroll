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
    $this->officer = userWithRole(Role::Payroll);
    $this->employeeUser = userWithRole(Role::Employee);
    $this->employee = Employee::factory()->monthly(30000)->forUser($this->employeeUser)->create([
        'hired_at' => '2020-01-01', 'sss_no' => '3412345678', 'philhealth_no' => '123456789012',
        'pagibig_no' => '121212121212', 'tin' => '123456789000',
    ]);

    for ($date = Carbon::parse('2026-10-01'); $date->lte('2026-10-31'); $date->addDay()) {
        if (! $date->isWeekend()) {
            TimeLog::query()->create(['employee_id' => $this->employee->id, 'logged_at' => $date->copy()->setTime(8, 0), 'type' => 'in', 'source' => 'import']);
            TimeLog::query()->create(['employee_id' => $this->employee->id, 'logged_at' => $date->copy()->setTime(17, 0), 'type' => 'out', 'source' => 'import']);
        }
    }

    // First cutoff finalized, second only computed.
    foreach ([['2026-10-01', '2026-10-15', true], ['2026-10-16', '2026-10-31', false]] as [$start, $end, $finalize]) {
        $run = PayrollRun::query()->create([
            'name' => "Payroll {$start}", 'type' => PayrollRunType::Regular, 'period_start' => $start,
            'period_end' => $end, 'pay_date' => $end, 'status' => PayrollRunStatus::Draft,
        ]);
        $this->actingAs($this->officer)->post("/payroll/runs/{$run->id}/compute");

        if ($finalize) {
            $this->actingAs($this->officer)->post("/payroll/runs/{$run->id}/finalize");
        }
    }
});

function csvRows(string $content): array
{
    return array_map('str_getcsv', array_values(array_filter(explode("\n", trim($content)))));
}

it('shows monthly totals from finalized runs only', function () {
    $this->actingAs($this->officer)->get('/payroll/reports?month=2026-10&year=2026')
        ->assertOk()
        ->assertSee('₱2,265.00')   // SSS 750 + 1,500 + EC 15
        ->assertSee('₱750.00')     // PhilHealth 375 + 375
        ->assertSee('₱503.70');    // tax withheld
});

it('exports the SSS, PhilHealth and Pag-IBIG remittance files', function () {
    $sss = csvRows($this->actingAs($this->officer)->get('/payroll/reports/sss.csv?month=2026-10')->streamedContent());
    expect($sss[0][0])->toBe('SSS No.')
        ->and($sss[1])->toBe(['34-1234567-8', $this->employee->last_name, $this->employee->first_name, mb_substr($this->employee->middle_name, 0, 1), '750.00', '1500.00', '15.00', '2265.00'])
        ->and($sss)->toHaveCount(2);

    $philhealth = csvRows($this->actingAs($this->officer)->get('/payroll/reports/philhealth.csv?month=2026-10')->streamedContent());
    expect(array_slice($philhealth[1], 4))->toBe(['30000.00', '375.00', '375.00', '750.00']);

    $pagibig = csvRows($this->actingAs($this->officer)->get('/payroll/reports/pagibig.csv?month=2026-10')->streamedContent());
    expect($pagibig[1][0])->toBe('1212-1212-1212')->and(array_slice($pagibig[1], 5))->toBe(['100.00', '100.00', '200.00']);

    $bir = csvRows($this->actingAs($this->officer)->get('/payroll/reports/bir-1601c.csv?month=2026-10')->streamedContent());
    expect(array_slice($bir[1], 4))->toBe(['15000.00', '13775.00', '503.70']);
});

it('rejects unknown agencies', function () {
    $this->actingAs($this->officer)->get('/payroll/reports/bogus.csv')->assertNotFound();
});

it('annualizes tax in the alphalist', function () {
    $rows = csvRows($this->actingAs($this->officer)->get('/payroll/reports/alphalist.csv?year=2026')->streamedContent());

    expect($rows)->toHaveCount(2)
        ->and($rows[1][0])->toBe('123-456-789-000')
        ->and(array_slice($rows[1], 5))->toBe([
            '15000.00', '0.00', '0.00', '0.00', '1225.00', '1225.00', '13775.00',
            '0.00',     // annual tax due on ₱13,775
            '503.70',   // withheld
            '-503.70',  // refund
        ]);
});

it('lets payroll staff and the employee download the 2316', function () {
    $this->actingAs($this->officer)->get("/payroll/reports/2316/2026/{$this->employee->id}")
        ->assertOk()->assertHeader('content-type', 'application/pdf');
    $this->actingAs($this->employeeUser)->get("/payroll/reports/2316/2026/{$this->employee->id}")->assertOk();

    $other = Employee::factory()->create();
    $this->actingAs($this->employeeUser)->get("/payroll/reports/2316/2026/{$other->id}")->assertForbidden();
    $this->actingAs($this->officer)->get("/payroll/reports/2316/2025/{$this->employee->id}")->assertNotFound();
});

it('restricts the reports to payroll staff', function () {
    $this->actingAs(userWithRole(Role::Hr))->get('/payroll/reports')->assertForbidden();
});
