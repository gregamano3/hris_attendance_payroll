<?php

namespace App\Features\Privacy\ExportMyData;

use App\Features\Employees\Queries\EmployeeDirectory;
use App\Features\Privacy\Enums\RequestStatus;
use App\Features\Privacy\Enums\RequestType;
use App\Features\Privacy\Models\DataSubjectRequest;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Right of access / data portability: the signed-in user's personal data as
 * a machine-readable JSON file. Logged as a completed access request.
 */
class ExportMyDataController
{
    public function __invoke(Request $request, EmployeeDirectory $directory, PersonalDataExport $export): Response
    {
        $user = $request->user() ?? abort(403);
        $employee = $directory->forUser($user);

        DataSubjectRequest::query()->create([
            'user_id' => $user->id, 'employee_id' => $employee?->id, 'type' => RequestType::Access,
            'status' => RequestStatus::Completed, 'details' => 'Self-service data export', 'handled_at' => now(),
        ]);

        return response()->json($export->for($user, $employee), 200, [
            'Content-Disposition' => 'attachment; filename="my-personal-data-'.now()->format('Ymd').'.json"',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
