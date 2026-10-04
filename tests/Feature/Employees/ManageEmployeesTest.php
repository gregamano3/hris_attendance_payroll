<?php

use App\Features\Employees\Enums\EmploymentStatus;
use App\Features\Employees\Models\Department;
use App\Features\Employees\Models\Employee;
use App\Features\Employees\Models\Position;
use App\Shared\Authorization\Role;

function validEmployeePayload(array $overrides = []): array
{
    $position = Position::factory()->create();

    return [
        'employee_no' => 'EMP-90001',
        'first_name' => 'Andres',
        'middle_name' => 'De Castro',
        'last_name' => 'Bonifacio',
        'department_id' => $position->department_id,
        'position_id' => $position->id,
        'employment_type' => 'probationary',
        'status' => 'active',
        'hired_at' => '2026-01-05',
        'rate_type' => 'monthly',
        'basic_rate' => '25,000.50',
        'sss_no' => '34-1234567-8',
        'philhealth_no' => '12-345678901-2',
        'pagibig_no' => '1234-5678-9012',
        'tin' => '123-456-789-000',
        ...$overrides,
    ];
}

it('lets HR create an employee and normalises the input', function () {
    $this->actingAs(userWithRole(Role::Hr))
        ->post('/employees', validEmployeePayload())
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $employee = Employee::query()->where('employee_no', 'EMP-90001')->firstOrFail();

    expect($employee->basic_rate->centavos)->toBe(2_500_050)
        ->and($employee->sss_no)->toBe('3412345678')
        ->and($employee->tin)->toBe('123456789000')
        ->and($employee->full_name)->toBe('Bonifacio, Andres D.');
});

it('validates government id lengths', function () {
    $this->actingAs(userWithRole(Role::Hr))
        ->post('/employees', validEmployeePayload(['sss_no' => '123', 'philhealth_no' => '1234567890123', 'tin' => '1234']))
        ->assertSessionHasErrors(['sss_no', 'philhealth_no', 'tin']);
});

it('requires a separation date for separated employees', function () {
    $this->actingAs(userWithRole(Role::Hr))
        ->post('/employees', validEmployeePayload(['status' => 'resigned']))
        ->assertSessionHasErrors('separated_at');
});

it('rejects duplicate employee numbers and government ids', function () {
    Employee::factory()->create(['employee_no' => 'EMP-90001', 'sss_no' => '3412345678']);

    $this->actingAs(userWithRole(Role::Hr))
        ->post('/employees', validEmployeePayload())
        ->assertSessionHasErrors(['employee_no', 'sss_no']);
});

it('updates an employee', function () {
    $employee = Employee::factory()->create();

    $this->actingAs(userWithRole(Role::Hr))
        ->put("/employees/{$employee->id}", validEmployeePayload([
            'employee_no' => $employee->employee_no,
            'status' => 'resigned',
            'separated_at' => '2026-09-30',
        ]))
        ->assertSessionHasNoErrors()
        ->assertRedirect("/employees/{$employee->id}");

    expect($employee->fresh()->status)->toBe(EmploymentStatus::Resigned);
});

it('archives an employee with a soft delete', function () {
    $employee = Employee::factory()->create();

    $this->actingAs(userWithRole(Role::Hr))
        ->delete("/employees/{$employee->id}")
        ->assertRedirect('/employees');

    $this->assertSoftDeleted($employee);
});

it('lists, searches and filters employees', function () {
    $it = Department::factory()->create();
    Employee::factory()->create(['first_name' => 'Gabriela', 'last_name' => 'Silang', 'department_id' => $it->id]);
    Employee::factory()->create(['first_name' => 'Emilio', 'last_name' => 'Aguinaldo']);

    $this->actingAs(userWithRole(Role::Payroll))
        ->get('/employees?search=gabriela silang')
        ->assertOk()
        ->assertSee('Silang, Gabriela')
        ->assertDontSee('Aguinaldo');

    $this->actingAs(userWithRole(Role::Payroll))
        ->get("/employees?department={$it->id}")
        ->assertSee('Silang')
        ->assertDontSee('Aguinaldo');
});

it('shows an employee profile with formatted government ids', function () {
    $employee = Employee::factory()->create(['sss_no' => '3412345678']);

    $this->actingAs(userWithRole(Role::Payroll))
        ->get("/employees/{$employee->id}")
        ->assertOk()
        ->assertSee('34-1234567-8')
        ->assertDontSee('Archive');
});

it('lets payroll view but not manage employees', function () {
    $user = userWithRole(Role::Payroll);
    $employee = Employee::factory()->create();

    $this->actingAs($user)->get('/employees')->assertOk();
    $this->actingAs($user)->get('/employees/create')->assertForbidden();
    $this->actingAs($user)->put("/employees/{$employee->id}", [])->assertForbidden();
    $this->actingAs($user)->delete("/employees/{$employee->id}")->assertForbidden();
});

it('forbids employees from the employee list', function () {
    $this->actingAs(userWithRole(Role::Employee))->get('/employees')->assertForbidden();
});

it('shows the logged in employee their own profile', function () {
    $user = userWithRole(Role::Employee);
    Employee::factory()->forUser($user)->create(['first_name' => 'Melchora', 'last_name' => 'Aquino']);

    $this->actingAs($user)->get('/my-profile')->assertOk()->assertSee('Melchora Aquino');
});

it('tells users without an employee record to contact HR', function () {
    $this->actingAs(userWithRole(Role::Employee))->get('/my-profile')->assertOk()->assertSee('not linked');
});

it('prefills the next employee number on the create form', function () {
    $this->actingAs(userWithRole(Role::Hr))->get('/employees/create')->assertOk()->assertSee('EMP-00001');
});

it('flags minimum wage earners', function () {
    $this->actingAs(userWithRole(Role::Hr))
        ->post('/employees', validEmployeePayload(['rate_type' => 'daily', 'basic_rate' => '695', 'is_minimum_wage_earner' => '1']))
        ->assertSessionHasNoErrors();

    expect(Employee::query()->where('employee_no', 'EMP-90001')->value('is_minimum_wage_earner'))->toBeTrue();
});
