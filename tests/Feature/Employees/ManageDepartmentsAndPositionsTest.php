<?php

use App\Features\Employees\Models\Department;
use App\Features\Employees\Models\Employee;
use App\Features\Employees\Models\Position;
use App\Features\Employees\Queries\EmployeeOptions;
use App\Shared\Authorization\Role;
use Illuminate\Support\Facades\Cache;

beforeEach(fn () => $this->actingAs(userWithRole(Role::Hr)));

it('creates, updates and deletes departments', function () {
    $this->post('/departments', ['code' => 'ops', 'name' => 'Operations'])->assertRedirect('/departments');

    $department = Department::query()->where('code', 'OPS')->firstOrFail();

    $this->put("/departments/{$department->id}", ['code' => 'OPS', 'name' => 'Ops & Logistics'])->assertRedirect('/departments');
    expect($department->fresh()->name)->toBe('Ops & Logistics');

    $this->delete("/departments/{$department->id}")->assertRedirect('/departments');
    $this->assertModelMissing($department);
});

it('does not delete departments that still have employees', function () {
    $employee = Employee::factory()->create();

    $this->delete("/departments/{$employee->department_id}")->assertSessionHas('error');
    $this->assertModelExists($employee->department);
});

it('validates unique department codes', function () {
    Department::factory()->create(['code' => 'FIN']);

    $this->post('/departments', ['code' => 'fin', 'name' => 'Finance 2'])->assertSessionHasErrors('code');
});

it('creates positions unique per department', function () {
    $department = Department::factory()->create();
    Position::factory()->create(['department_id' => $department->id, 'title' => 'Clerk']);

    $this->post('/positions', ['department_id' => $department->id, 'title' => 'Clerk'])->assertSessionHasErrors('title');
    $this->post('/positions', ['department_id' => null, 'title' => 'Clerk'])->assertSessionHasNoErrors();
});

it('caches select options and flushes them on change', function () {
    Department::factory()->create(['name' => 'Alpha']);

    expect(app(EmployeeOptions::class)->departments())->toContain('Alpha')
        ->and(Cache::has(EmployeeOptions::DEPARTMENTS_KEY))->toBeTrue();

    Department::factory()->create(['name' => 'Beta']);

    expect(Cache::has(EmployeeOptions::DEPARTMENTS_KEY))->toBeFalse()
        ->and(app(EmployeeOptions::class)->departments())->toContain('Beta');
});

it('forbids payroll officers from managing departments', function () {
    $this->actingAs(userWithRole(Role::Payroll))->get('/departments')->assertForbidden();
});
