<?php

namespace App\Features\Attendance\ImportTimeLogs;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ImportTimeLogsController
{
    public function create(): View
    {
        return view('attendance::time-logs.import');
    }

    public function store(Request $request, ImportTimeLogsAction $import): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $result = $import->handle($request->file('file')->getRealPath(), $request->user()?->id);

        return redirect()->route('attendance.import.create')
            ->with($result['imported'] > 0 ? 'success' : 'warning', "Imported {$result['imported']} time log(s), skipped {$result['skipped']} duplicate(s).")
            ->with('import_errors', $result['errors']);
    }
}
