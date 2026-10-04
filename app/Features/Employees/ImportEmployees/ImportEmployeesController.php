<?php

namespace App\Features\Employees\ImportEmployees;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportEmployeesController
{
    public function create(): View
    {
        return view('employees::employees.import', ['columns' => ImportEmployeesAction::COLUMNS]);
    }

    public function store(Request $request, ImportEmployeesAction $import): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:5120']]);

        $result = $import->handle($request->file('file')->getRealPath());

        if ($result['errors'] !== []) {
            return back()->with('error', 'Nothing was imported. Fix the rows below and upload the file again.')
                ->with('import_errors', $result['errors']);
        }

        return redirect()->route('employees.index')->with('success', "Imported {$result['imported']} employee(s).");
    }

    public function template(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ImportEmployeesAction::COLUMNS);
            fputcsv($out, ImportEmployeesAction::sampleRow());
            fclose($out);
        }, 'employees-template.csv', ['Content-Type' => 'text/csv']);
    }
}
