<?php

use App\Features\Employees\Models\Employee;
use App\Features\Employees\Models\EmployeeDocument;
use App\Shared\Authorization\Role;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->hr = userWithRole(Role::Hr);
    $this->user = userWithRole(Role::Employee);
    $this->employee = Employee::factory()->forUser($this->user)->create();
});

function upload($test, Employee $employee): EmployeeDocument
{
    $test->actingAs($test->hr)->post("/employees/{$employee->id}/documents", [
        'category' => 'contract', 'title' => 'Employment contract',
        'file' => UploadedFile::fake()->create('contract.pdf', 120, 'application/pdf'),
    ])->assertSessionHas('success');

    return EmployeeDocument::query()->latest('id')->firstOrFail();
}

it('stores uploads on the private disk', function () {
    $document = upload($this, $this->employee);

    Storage::disk('local')->assertExists($document->path);
    expect($document->path)->toStartWith("employee-documents/{$this->employee->id}/")
        ->and($document->original_name)->toBe('contract.pdf')
        ->and($document->uploaded_by)->toBe($this->hr->id);

    $this->actingAs($this->hr)->get("/employees/{$this->employee->id}")->assertSee('Employment contract');
});

it('validates file types and size', function () {
    $this->actingAs($this->hr)->post("/employees/{$this->employee->id}/documents", [
        'category' => 'contract', 'title' => 'Script', 'file' => UploadedFile::fake()->create('evil.exe', 10),
    ])->assertSessionHasErrors('file');

    $this->actingAs($this->hr)->post("/employees/{$this->employee->id}/documents", [
        'category' => 'contract', 'title' => 'Huge', 'file' => UploadedFile::fake()->create('big.pdf', 20000, 'application/pdf'),
    ])->assertSessionHasErrors('file');
});

it('lets staff and the owner download, but nobody else', function () {
    $document = upload($this, $this->employee);

    $this->actingAs($this->hr)->get("/documents/{$document->id}")->assertOk()->assertDownload('contract.pdf');
    $this->actingAs($this->user)->get("/documents/{$document->id}")->assertOk();
    $this->actingAs($this->user)->get('/my-profile')->assertSee('Employment contract');

    $stranger = userWithRole(Role::Employee);
    Employee::factory()->forUser($stranger)->create();
    $this->actingAs($stranger)->get("/documents/{$document->id}")->assertForbidden();
});

it('requires authentication to download', function () {
    $document = EmployeeDocument::query()->create([
        'employee_id' => $this->employee->id, 'category' => 'other', 'title' => 'x', 'path' => 'x.pdf',
        'original_name' => 'x.pdf', 'mime_type' => 'application/pdf', 'size' => 1,
    ]);

    $this->get("/documents/{$document->id}")->assertRedirect('/login');
});

it('deletes the file with the record', function () {
    $document = upload($this, $this->employee);

    $this->actingAs($this->user)->delete("/documents/{$document->id}")->assertForbidden();
    $this->actingAs($this->hr)->delete("/documents/{$document->id}")->assertSessionHas('success');

    $this->assertModelMissing($document);
    Storage::disk('local')->assertMissing($document->path);
});
