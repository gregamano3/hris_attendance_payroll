<?php

namespace App\Features\Employees\ManageDocuments;

use App\Features\Employees\Enums\DocumentCategory;
use App\Features\Employees\Models\Employee;
use App\Features\Employees\Models\EmployeeDocument;
use App\Features\Employees\Queries\EmployeeDirectory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Employee 201 file. Files live on the private disk and are only streamed
 * after an authorization check — never exposed through a public URL.
 */
class DocumentsController
{
    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $data = $request->validate([
            'category' => ['required', Rule::enum(DocumentCategory::class)],
            'title' => ['required', 'string', 'max:150'],
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx'],
        ]);

        $file = $request->file('file');
        $path = $file->store("employee-documents/{$employee->id}", EmployeeDocument::DISK);

        $employee->documents()->create([
            'category' => $data['category'],
            'title' => $data['title'],
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
            'size' => $file->getSize(),
            'uploaded_by' => $request->user()?->id,
        ]);

        return back()->with('success', 'Document uploaded.');
    }

    public function download(Request $request, EmployeeDocument $document, EmployeeDirectory $directory): StreamedResponse
    {
        $user = $request->user();
        abort_unless($user?->can('employees.view') || $directory->forUser($user)?->is($document->employee), 403);

        return Storage::disk(EmployeeDocument::DISK)->download($document->path, $document->original_name);
    }

    public function destroy(EmployeeDocument $document): RedirectResponse
    {
        $document->delete();

        return back()->with('success', 'Document deleted.');
    }
}
