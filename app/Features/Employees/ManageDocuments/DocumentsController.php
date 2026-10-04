<?php

namespace App\Features\Employees\ManageDocuments;

use App\Features\Employees\Enums\DocumentCategory;
use App\Features\Employees\Models\Employee;
use App\Features\Employees\Models\EmployeeDocument;
use App\Features\Employees\Queries\EmployeeDirectory;
use App\Shared\Security\EncryptedFiles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Employee 201 file. Files are encrypted at rest on the private disk and
 * only decrypted and streamed after an authorization check.
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
        $path = "employee-documents/{$employee->id}/".Str::uuid().'.enc';
        EncryptedFiles::put(EmployeeDocument::DISK, $path, (string) file_get_contents($file->getRealPath()));

        $employee->documents()->create([
            'category' => $data['category'],
            'title' => $data['title'],
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
            'size' => $file->getSize(),
            'is_encrypted' => true,
            'uploaded_by' => $request->user()?->id,
        ]);

        return back()->with('success', 'Document uploaded.');
    }

    public function download(Request $request, EmployeeDocument $document, EmployeeDirectory $directory): StreamedResponse
    {
        $user = $request->user();
        abort_unless($user?->can('employees.view') || $directory->forUser($user)?->is($document->employee), 403);

        if (! $document->is_encrypted) {
            return Storage::disk(EmployeeDocument::DISK)->download($document->path, $document->original_name);
        }

        $contents = EncryptedFiles::get(EmployeeDocument::DISK, $document->path);

        return response()->streamDownload(fn () => print ($contents), $document->original_name, ['Content-Type' => $document->mime_type]);
    }

    public function destroy(EmployeeDocument $document): RedirectResponse
    {
        $document->delete();

        return back()->with('success', 'Document deleted.');
    }
}
