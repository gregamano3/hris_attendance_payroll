<?php

use App\Features\Employees\Models\Department;
use App\Features\Employees\Models\Employee;
use App\Features\Employees\Models\Position;
use App\Shared\Authorization\Role;
use Illuminate\Http\UploadedFile;

beforeEach(function () {
    $this->hr = userWithRole(Role::Hr);
    $ops = Department::factory()->create(['code' => 'OPS', 'name' => 'Operations']);
    Position::factory()->create(['department_id' => $ops->id, 'title' => 'Production Staff']);
});

function importCsv($test, array $lines)
{
    return $test->actingAs($test->hr)->post('/employees/import', [
        'file' => UploadedFile::fake()->createWithContent('employees.csv', implode("\n", $lines)),
    ]);
}

it('downloads a template that imports cleanly', function () {
    $template = $this->actingAs($this->hr)->get('/employees/import/template.csv')->assertOk()->streamedContent();

    importCsv($this, explode("\n", trim($template)))->assertRedirect('/employees')->assertSessionHas('success', 'Imported 1 employee(s).');

    $employee = Employee::query()->where('employee_no', 'EMP-10001')->with(['department', 'position'])->sole();
    expect($employee->department->code)->toBe('OPS')
        ->and($employee->position->title)->toBe('Production Staff')
        ->and($employee->basic_rate->toDecimal())->toBe('645.00')
        ->and($employee->is_minimum_wage_earner)->toBeTrue()
        ->and($employee->sss_no)->toBe('3412345678')
        ->and($employee->bank_account_no)->toBe('001234567890');
});

it('imports nothing when any row is invalid and reports every problem', function () {
    importCsv($this, [
        'employee_no,first_name,last_name,employment_type,hired_at,rate_type,basic_rate,department_code,sss_no',
        'EMP-1,Ana,Reyes,regular,2024-01-01,monthly,30000,OPS,34-1234567-8',
        'EMP-2,Ben,Cruz,regular,2024-01-01,monthly,abc,XYZ,',
        'EMP-1,Cara,Lim,contract-ish,2024-01-01,monthly,20000,,34-1234567-8',
    ])->assertSessionHas('error')
        ->assertSessionHas('import_errors', function (array $errors) {
            $all = implode(' ', $errors);

            return count($errors) === 2
                && str_contains($all, 'Line 3: unknown department code [XYZ]')
                && str_contains($all, 'basic rate')
                && str_contains($all, 'duplicate employee_no in the file (also on line 2)')
                && str_contains($all, 'duplicate sss_no');
        });

    expect(Employee::query()->count())->toBe(0);
});

it('rejects files missing required columns', function () {
    importCsv($this, ['employee_no,first_name', 'EMP-1,Ana'])
        ->assertSessionHas('import_errors', fn ($e) => str_starts_with($e[0], 'Missing columns: last_name'));
});

it('rejects employee numbers that already exist', function () {
    Employee::factory()->create(['employee_no' => 'EMP-1']);

    importCsv($this, [
        'employee_no,first_name,last_name,employment_type,hired_at,rate_type,basic_rate',
        'EMP-1,Ana,Reyes,regular,2024-01-01,monthly,30000',
    ])->assertSessionHas('import_errors', fn ($e) => str_contains($e[0], 'employee no'));
});

it('restricts imports to employee managers', function () {
    $this->actingAs(userWithRole(Role::Payroll))->get('/employees/import')->assertForbidden();
});
