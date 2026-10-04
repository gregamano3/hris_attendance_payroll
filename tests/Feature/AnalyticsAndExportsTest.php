<?php

use App\Features\Analytics\ShowAnalytics\HrAnalytics;
use App\Features\Attendance\Models\Shift;
use App\Features\Attendance\Models\TimeLog;
use App\Features\Employees\Models\Department;
use App\Features\Employees\Models\Employee;
use App\Features\Payroll\Enums\PayrollRunStatus;
use App\Features\Payroll\Enums\PayrollRunType;
use App\Features\Payroll\Models\PayrollRun;
use App\Shared\Authorization\Role;
use App\Shared\Money\Money;
use Database\Seeders\PayrollSeeder;
use Illuminate\Support\Carbon;
use OpenSpout\Reader\XLSX\Reader;

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-10-20 12:00'));
    $this->hr = userWithRole(Role::Hr);
});

/**
 * @return list<list<mixed>>
 */
function readXlsx(string $content): array
{
    $path = tempnam(sys_get_temp_dir(), 'xlsx').'.xlsx';
    file_put_contents($path, $content);
    $reader = new Reader;
    $reader->open($path);
    $rows = [];

    foreach ($reader->getSheetIterator() as $sheet) {
        foreach ($sheet->getRowIterator() as $row) {
            $rows[] = $row->toArray();
        }
        break;
    }

    $reader->close();
    unlink($path);

    return $rows;
}

it('computes headcount, movement and payroll cost', function () {
    $ops = Department::factory()->create(['name' => 'Operations']);
    Employee::factory()->count(3)->create(['department_id' => $ops->id, 'hired_at' => '2026-02-10']);
    Employee::factory()->separated()->create(['hired_at' => '2020-01-01', 'separated_at' => '2026-09-15']);
    PayrollRun::query()->create(['name' => 'P', 'type' => PayrollRunType::Regular, 'period_start' => '2026-09-01', 'period_end' => '2026-09-15',
        'pay_date' => '2026-09-15', 'status' => PayrollRunStatus::Finalized, 'total_gross' => Money::ofPesos(100000), 'total_employer' => Money::ofPesos(9000)]);

    $data = app(HrAnalytics::class)->snapshot();

    expect($data['headcount'])->toBe(3)
        ->and($data['by_department']['Operations'])->toBe(3)
        ->and(collect($data['movement'])->firstWhere('month', 'Feb 2026')['hires'])->toBe(3)
        ->and(collect($data['movement'])->firstWhere('month', 'Sep 2026')['separations'])->toBe(1)
        ->and(collect($data['payroll_cost'])->firstWhere('month', 'Sep 2026'))->toBe(['month' => 'Sep 2026', 'gross' => 100000.0, 'employer' => 9000.0]);

    $this->actingAs($this->hr)->get('/analytics')->assertOk()->assertSee('chart-movement', false);
    expect(readXlsx($this->actingAs($this->hr)->get('/analytics/export.xlsx')->assertOk()->streamedContent())[0][0])->toBe('Month');
    $this->actingAs(userWithRole(Role::Employee))->get('/analytics')->assertForbidden();
});

it('exports the filtered employee list without government IDs', function () {
    $ops = Department::factory()->create(['name' => 'Operations']);
    Employee::factory()->create(['department_id' => $ops->id, 'last_name' => 'Rizal', 'sss_no' => '3412345678']);
    Employee::factory()->create(['last_name' => 'Bonifacio']);

    $rows = readXlsx($this->actingAs($this->hr)->get("/employees/export.xlsx?department={$ops->id}")->assertOk()->streamedContent());

    expect($rows)->toHaveCount(2)
        ->and($rows[1][1])->toBe('Rizal')
        ->and(json_encode($rows))->not->toContain('3412345678');
});

it('exports the DTR and the payroll register to Excel', function () {
    $this->seed(PayrollSeeder::class);
    Shift::factory()->default()->create();
    $employee = Employee::factory()->monthly(30000)->create(['hired_at' => '2020-01-01']);
    TimeLog::query()->create(['employee_id' => $employee->id, 'logged_at' => '2026-10-05 08:20', 'type' => 'in', 'source' => 'web']);
    TimeLog::query()->create(['employee_id' => $employee->id, 'logged_at' => '2026-10-05 17:00', 'type' => 'out', 'source' => 'web']);

    $dtr = readXlsx($this->actingAs($this->hr)->get("/attendance/dtr/{$employee->id}/xlsx?from=2026-10-05&to=2026-10-06")->assertOk()->streamedContent());
    expect($dtr[1])->toBe(['2026-10-05', 'Mon', 'Present', '08:20', '17:00', 460, 20, 0, 0, 0]);

    $officer = userWithRole(Role::Payroll);
    $run = PayrollRun::query()->create(['name' => 'P', 'type' => PayrollRunType::Regular, 'period_start' => '2026-10-01', 'period_end' => '2026-10-15', 'pay_date' => '2026-10-15', 'status' => PayrollRunStatus::Draft]);
    $this->actingAs($officer)->post("/payroll/runs/{$run->id}/compute");

    $register = readXlsx($this->actingAs($officer)->get("/payroll/runs/{$run->id}/register.xlsx")->assertOk()->streamedContent());
    expect($register[0][0])->toBe('Employee no.')->and($register[1][0])->toBe($employee->employee_no)->and($register[1][5])->toEqual(15000);
});
